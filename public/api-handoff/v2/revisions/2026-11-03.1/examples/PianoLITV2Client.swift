// UIKit-compatible connection example. Add to the NEW app target only.
// Keep the existing login screen. Replace its network call with signIn(),
// then fetch discover(). Store no password or bearer credential in UserDefaults.
import Foundation
import Security

struct V2User: Codable, Sendable {
    let id: String
    let first_name: String
    let last_name: String
}

struct V2Session: Codable, Sendable {
    let access_token: String
    let token_type: String
    let expires_at: String
    let user: V2User
}

struct V2Envelope<T: Decodable>: Decodable {
    let data: T
}

struct V2Failure: Decodable, Sendable {
    struct Detail: Decodable, Sendable {
        let code: String
        let message: String
    }
    let error: Detail
}

enum V2ClientError: Error {
    case signInRequired
    case http(Int, V2Failure?)
    case keychain(OSStatus)
    case invalidResponse
}

struct V2Target: Decodable, Sendable {
    let kind: String
    let id: String?
    let query: String?
    let web_url: URL
}

struct V2Composer: Decodable, Sendable {
    let id: String
    let name: String
    let short_name: String
    let portrait_url: URL
    let biography: String
    let piece_count: Int
    let target: V2Target
}

struct V2Piece: Decodable, Sendable {
    struct Media: Decodable, Sendable {
        let performance: Bool
        let synthesia: Bool
        let tutorial: Bool
        let audio: Bool
        let score: Bool
    }
    let id: String
    let title: String
    let featured_title: String
    let subtitle: String
    let catalogue: String
    let composer: V2Composer
    let artwork_url: URL
    let difficulty: String?
    let is_new: Bool
    let is_free_pick: Bool
    let media: Media
    let target: V2Target
    // Availability and is_free_pick are display metadata, not playback grants.
}

struct V2Level: Decodable, Sendable {
    let id: String
    let title: String
    let number: Int
    let piece_count: Int
    let target: V2Target
}

enum V2Item: Decodable, Sendable {
    case piece(V2Piece)
    case composer(V2Composer)
    case unsupported
    private enum CodingKeys: String, CodingKey { case kind }
    init(from decoder: Decoder) throws {
        let values = try decoder.container(keyedBy: CodingKeys.self)
        switch try values.decode(String.self, forKey: .kind) {
        case "piece": self = .piece(try V2Piece(from: decoder))
        case "composer": self = .composer(try V2Composer(from: decoder))
        default: self = .unsupported
        }
    }
}

struct V2Discover: Decodable, Sendable {
    struct Section: Decodable, Sendable {
        struct Group: Decodable, Sendable {
            let id: String
            let label: String
            let items: [V2Level]
        }
        let id: String
        let kind: String
        let title: String
        let subtitle: String?
        let items: [V2Item]?
        let groups: [Group]?
        let default_group: String?
        let action: V2Target?
        let featured_composer: V2Composer?
        private enum CodingKeys: String, CodingKey {
            case id, kind, title, subtitle, items, groups, default_group, action, featured_composer
        }
        init(from decoder: Decoder) throws {
            let fields = try decoder.container(keyedBy: CodingKeys.self)
            id = try fields.decode(String.self, forKey: .id)
            kind = try fields.decode(String.self, forKey: .kind)
            let supported = ["featured_piece", "portrait_rail", "compact_piece_rail", "artwork_piece_rail",
                             "personalized_piece_rail", "composer_feature", "highlight_rail", "level_selector"]
            guard supported.contains(kind) else {
                title = ""; subtitle = nil; items = nil; groups = nil
                default_group = nil; action = nil; featured_composer = nil
                return
            }
            title = try fields.decode(String.self, forKey: .title)
            subtitle = try fields.decodeIfPresent(String.self, forKey: .subtitle)
            items = try fields.decodeIfPresent([V2Item].self, forKey: .items)
            groups = try fields.decodeIfPresent([Group].self, forKey: .groups)
            default_group = try fields.decodeIfPresent(String.self, forKey: .default_group)
            action = try fields.decodeIfPresent(V2Target.self, forKey: .action)
            featured_composer = try fields.decodeIfPresent(V2Composer.self, forKey: .featured_composer)
        }
    }
    let sections: [Section]
    // Render by kind/id, never row position. Skip unknown kinds.
}

private enum V2Keychain {
    static var query: [String: Any] {
        [kSecClass as String: kSecClassGenericPassword,
         kSecAttrService as String: "com.pianolit.native.api-v2",
         kSecAttrAccount as String: "session"]
    }
    static func load() throws -> V2Session? {
        var lookup = query
        lookup[kSecReturnData as String] = true
        lookup[kSecMatchLimit as String] = kSecMatchLimitOne
        var item: CFTypeRef?
        let status = SecItemCopyMatching(lookup as CFDictionary, &item)
        if status == errSecItemNotFound { return nil }
        guard status == errSecSuccess else { throw V2ClientError.keychain(status) }
        guard let bytes = item as? Data else { throw V2ClientError.invalidResponse }
        return try JSONDecoder().decode(V2Session.self, from: bytes)
    }
    static func save(_ session: V2Session) throws {
        let bytes = try JSONEncoder().encode(session)
        let update = [kSecValueData as String: bytes]
        var status = SecItemUpdate(query as CFDictionary, update as CFDictionary)
        if status == errSecItemNotFound {
            var insert = query
            insert[kSecValueData as String] = bytes
            insert[kSecAttrAccessible as String] = kSecAttrAccessibleWhenUnlockedThisDeviceOnly
            status = SecItemAdd(insert as CFDictionary, nil)
        }
        guard status == errSecSuccess else { throw V2ClientError.keychain(status) }
    }
    static func clear() throws {
        let status = SecItemDelete(query as CFDictionary)
        guard status == errSecSuccess || status == errSecItemNotFound else {
            throw V2ClientError.keychain(status)
        }
    }
}

actor PianoLITV2Client {
    // Keep a fixed, verified origin. Never attach this bearer to media, web_url,
    // card.source or legacy requests. This is the deployed API, not the YAML URL.
    private let base = URL(string: "https://pianolit.com/api/v2/")!
    private let transport: URLSession
    private var session: V2Session?
    private var generation = UUID()

    init() throws {
        let config = URLSessionConfiguration.ephemeral
        config.httpCookieStorage = nil
        config.httpShouldSetCookies = false
        config.urlCache = nil
        transport = URLSession(configuration: config, delegate: V2NoRedirects(), delegateQueue: nil)
        session = try V2Keychain.load()
    }

    func currentUser() -> V2User? { session?.user }

    func signIn(email: String, password: String) async throws -> V2User {
        generation = UUID()
        let current = generation
        session = nil
        try V2Keychain.clear()
        let body = try JSONSerialization.data(withJSONObject: ["email": email, "password": password])
        let bytes = try await send("auth/login", method: "POST", body: body, bearer: nil)
        try Task.checkCancellation()
        guard generation == current else { throw CancellationError() }
        let result = try JSONDecoder().decode(V2Envelope<V2Session>.self, from: bytes).data
        try V2Keychain.save(result)
        session = result
        return result.user // Map these identity fields into the existing UI.
    }

    func discover() async throws -> V2Discover {
        let bytes = try await authenticated("discover", method: "GET")
        return try JSONDecoder().decode(V2Envelope<V2Discover>.self, from: bytes).data
    }

    func recordConfirmedOpen(pieceID: String) async throws {
        guard !pieceID.isEmpty, pieceID.utf8.allSatisfy({ $0 >= 48 && $0 <= 57 }), pieceID.first != "0" else {
            throw V2ClientError.invalidResponse
        }
        _ = try await authenticated("pieces/\(pieceID)/view", method: "POST")
    }

    func signOut() async throws {
        let credential = session?.access_token
        generation = UUID()
        session = nil
        try V2Keychain.clear()
        // Clear the UI/feed/images for this account immediately. If offline,
        // local logout still completes; server revocation has not succeeded.
        if let credential { _ = try await send("auth/logout", method: "POST", body: nil, bearer: credential) }
    }

    private func authenticated(_ path: String, method: String) async throws -> Data {
        guard let credential = session?.access_token else { throw V2ClientError.signInRequired }
        let current = generation
        do {
            let bytes = try await send(path, method: method, body: nil, bearer: credential)
            try Task.checkCancellation()
            guard generation == current else { throw CancellationError() }
            return bytes
        } catch V2ClientError.http(401, _) {
            if generation == current {
                generation = UUID()
                session = nil
                try V2Keychain.clear()
            }
            throw V2ClientError.signInRequired
        }
    }

    private func send(_ path: String, method: String, body: Data?, bearer: String?) async throws -> Data {
        var request = URLRequest(url: base.appendingPathComponent(path))
        request.httpMethod = method
        request.httpBody = body
        request.timeoutInterval = 30
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        if body != nil { request.setValue("application/json", forHTTPHeaderField: "Content-Type") }
        if let bearer { request.setValue("Bearer \(bearer)", forHTTPHeaderField: "Authorization") }
        let (bytes, response) = try await transport.data(for: request)
        guard let http = response as? HTTPURLResponse else { throw V2ClientError.invalidResponse }
        guard (200...299).contains(http.statusCode) else {
            throw V2ClientError.http(http.statusCode, try? JSONDecoder().decode(V2Failure.self, from: bytes))
        }
        return bytes
    }
}

private final class V2NoRedirects: NSObject, URLSessionTaskDelegate, @unchecked Sendable {
    func urlSession(_ session: URLSession, task: URLSessionTask,
                    willPerformHTTPRedirection response: HTTPURLResponse,
                    newRequest request: URLRequest,
                    completionHandler: @escaping (URLRequest?) -> Void) {
        completionHandler(nil)
    }
}
