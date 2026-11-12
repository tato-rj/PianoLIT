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
    case rateLimited(retryAfter: TimeInterval?)
}

struct V2FavoriteFolder: Decodable, Sendable {
    let id: String
    let name: String
    let piece_count: Int
    let has_piece: Bool? // nil only when creating without a selected piece
}

struct V2FavoriteFolderList: Decodable, Sendable {
    let piece_id: String
    let is_favorited: Bool
    let favorite_folder_ids: [String]
    let folders: [V2FavoriteFolder]
    let can_create_folder: Bool
}

struct V2FavoriteFolderChange: Decodable, Sendable {
    let piece_id: String?
    let is_favorited: Bool?
    let favorite_folder_ids: [String]
    let folder: V2FavoriteFolder
    let can_create_folder: Bool
}

struct V2Target: Decodable, Sendable {
    let kind: String
    let id: String?
    let query: String?
    let web_url: URL
}

struct V2Composer: Decodable, Sendable {
    struct Country: Decodable, Sendable {
        let id: String
        let name: String
        let flag_code: String?
    }
    let id: String
    let name: String
    let short_name: String
    let portrait_url: URL
    let biography: String
    let piece_count: Int
    let target: V2Target
    let lifespan: String?
    let born_year: Int?
    let died_year: Int?
    let country: Country?
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

struct V2Collection: Decodable, Sendable {
    let id: String
    let title: String
    let subtitle: String?
    let artwork_url: URL
    let piece_count: Int
    let featured_badge: String?
    let access: String
    let description: String? // Included only on detail; may be null.
}

struct V2CollectionCatalog: Decodable, Sendable {
    let featured_collection_id: String?
    let collections: [V2Collection]
    // Optional so the same decoder can read fixtures from the original handoff.
    let book_series: [V2CollectionBookSeries]?
    let suggested_collection_ids: [String]?
    let categories: [V2CollectionCategory]?
}

struct V2CollectionBookSeries: Decodable, Sendable {
    let id: String
    let title: String
    let books: [V2CollectionBook]
}

struct V2CollectionBook: Decodable, Sendable {
    let id: String
    let number: Int
    let title: String
    let level: String
    let cover_url: URL
    let availability: String
    let detail_url: URL?
}

struct V2CollectionCategory: Decodable, Sendable {
    let id: String
    let label: String
    let collection_ids: [String]
}

struct V2CollectionDetail: Decodable, Sendable {
    let collection: V2Collection
    let pieces: [V2Piece]
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

    func piece(id: String) async throws -> V2PieceDetail {
        try validateDecimalID(id)
        let bytes = try await authenticated("pieces/\(id)", method: "GET")
        return try JSONDecoder().decode(V2Envelope<V2PieceDetail>.self, from: bytes).data
    }

    func collections() async throws -> V2CollectionCatalog {
        let bytes = try await authenticated("collections", method: "GET")
        return try JSONDecoder().decode(V2Envelope<V2CollectionCatalog>.self, from: bytes).data
    }

    func collection(id: String) async throws -> V2CollectionDetail {
        try validateDecimalID(id)
        let bytes = try await authenticated("collections/\(id)", method: "GET")
        return try JSONDecoder().decode(V2Envelope<V2CollectionDetail>.self, from: bytes).data
    }

    func recordConfirmedOpen(pieceID: String) async throws {
        try validateDecimalID(pieceID)
        _ = try await authenticated("pieces/\(pieceID)/view", method: "POST")
    }

    func favoriteFolders(pieceID: String) async throws -> V2FavoriteFolderList {
        try validateDecimalID(pieceID)
        let bytes = try await authenticated("favorite-folders", method: "GET",
            query: [URLQueryItem(name: "piece_id", value: pieceID)])
        return try JSONDecoder().decode(V2Envelope<V2FavoriteFolderList>.self, from: bytes).data
    }

    func saveFavorite(folderID: String, pieceID: String) async throws -> V2FavoriteFolderChange {
        try await changeFavorite(folderID: folderID, pieceID: pieceID, method: "PUT")
    }

    func removeFavorite(folderID: String, pieceID: String) async throws -> V2FavoriteFolderChange {
        try await changeFavorite(folderID: folderID, pieceID: pieceID, method: "DELETE")
    }

    private func changeFavorite(folderID: String, pieceID: String, method: String) async throws -> V2FavoriteFolderChange {
        try validateDecimalID(folderID)
        try validateDecimalID(pieceID)
        let bytes = try await authenticated("favorite-folders/\(folderID)/pieces/\(pieceID)", method: method)
        return try JSONDecoder().decode(V2Envelope<V2FavoriteFolderChange>.self, from: bytes).data
    }

    // Keep the same UUID and payload for all retries of one submission.
    func createFavoriteFolder(name: String, pieceID: String?, idempotencyKey: UUID) async throws -> V2FavoriteFolderChange {
        struct Payload: Encodable { let name: String; let piece_id: String? }
        if let pieceID { try validateDecimalID(pieceID) }
        let body = try JSONEncoder().encode(Payload(name: name, piece_id: pieceID))
        let bytes = try await authenticated("favorite-folders", method: "POST", body: body,
            idempotencyKey: idempotencyKey)
        return try JSONDecoder().decode(V2Envelope<V2FavoriteFolderChange>.self, from: bytes).data
    }

    private func validateDecimalID(_ id: String) throws {
        guard !id.isEmpty, id.utf8.allSatisfy({ $0 >= 48 && $0 <= 57 }), id.first != "0" else {
            throw V2ClientError.invalidResponse
        }
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

    private func authenticated(_ path: String, method: String, body: Data? = nil,
                               query: [URLQueryItem] = [], idempotencyKey: UUID? = nil) async throws -> Data {
        guard let credential = session?.access_token else { throw V2ClientError.signInRequired }
        let current = generation
        do {
            let bytes = try await send(path, method: method, body: body, bearer: credential,
                query: query, idempotencyKey: idempotencyKey)
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

    private func send(_ path: String, method: String, body: Data?, bearer: String?,
                      query: [URLQueryItem] = [], idempotencyKey: UUID? = nil) async throws -> Data {
        guard var components = URLComponents(url: base.appendingPathComponent(path), resolvingAgainstBaseURL: false) else {
            throw V2ClientError.invalidResponse
        }
        if !query.isEmpty { components.queryItems = query }
        guard let url = components.url else { throw V2ClientError.invalidResponse }
        var request = URLRequest(url: url)
        request.httpMethod = method
        request.httpBody = body
        request.timeoutInterval = 30
        request.setValue("application/json", forHTTPHeaderField: "Accept")
        if body != nil { request.setValue("application/json", forHTTPHeaderField: "Content-Type") }
        if let bearer { request.setValue("Bearer \(bearer)", forHTTPHeaderField: "Authorization") }
        if let idempotencyKey { request.setValue(idempotencyKey.uuidString, forHTTPHeaderField: "Idempotency-Key") }
        let (bytes, response) = try await transport.data(for: request)
        guard let http = response as? HTTPURLResponse else { throw V2ClientError.invalidResponse }
        if http.statusCode == 429 {
            throw V2ClientError.rateLimited(retryAfter: http.value(forHTTPHeaderField: "Retry-After").flatMap(TimeInterval.init))
        }
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

// New piece page: let detail = try await client.piece(id: discoverCard.id)
// Render metadata for every authenticated account. Play/read a resource only
// when its mode == "full" AND its URL is non-nil. Never forward the API bearer
// to a media, purchase, timeline-source or public web URL.
struct V2PieceDetail: Decodable, Sendable {
    struct Metadata: Decodable, Sendable {
        struct Tag: Decodable, Sendable {
            let id: String
            let name: String
            let type: String?
        }
        let name: String
        let short_name: String
        let long_name: String
        let nickname: String?
        let key: String?
        let collection_name: String?
        let collection_number: String?
        let movement_number: String?
        let period: String?
        let level: String?
        let page_count_label: String?
        let composed_year: Int?
        let published_year: Int?
        let tags: [Tag]
    }
    struct About: Decodable, Sendable {
        let description: String?
        let description_fallback: String?
        let for_whom: String?
        let rankings: [String: String?]
    }
    struct Account: Decodable, Sendable {
        let is_favorited: Bool
        let favorite_writes_supported: Bool
        let annotations_supported: Bool
        let history_path: String
    }
    struct Access: Decodable, Sendable {
        let full_media: Bool
        let basis: String
    }
    struct Moment: Decodable, Sendable {
        let id: String
        let start_seconds: Double
        let end_seconds: Double?
        let title: String
        let comment: String?
    }
    struct Video: Decodable, Sendable {
        let id: String
        let title: String
        let category: String?
        let mode: String
        let url: URL?
        let moments: [Moment]
    }
    struct Media: Decodable, Sendable {
        struct Lesson: Decodable, Sendable {
            let title: String
            let videos: [Video]
        }
        struct Audio: Decodable, Sendable {
            struct Track: Decodable, Sendable {
                let kind: String
                let url: URL?
            }
            let mode: String
            let tracks: [Track]
            let moments: [Moment]
        }
        struct Score: Decodable, Sendable {
            let mode: String
            let is_public_domain: Bool
            let pdf_url: URL?
            let purchase_url: URL?
            let version: String?
            let editor: String?
            let publisher: String?
            let copyright: String?
        }
        let performance: Video?
        let synthesia: Video?
        let lessons: [Lesson]
        let audio: Audio
        let score: Score
    }
    struct Timeline: Decodable, Sendable {
        struct Period: Decodable, Sendable {
            let born: Int?
            let died: Int?
            let start_year: Int
            let end_year: Int
        }
        struct Event: Decodable, Sendable {
            let id: String
            let year: Int
            let date: String?
            let title: String
            let description: String
            let is_piece_milestone: Bool
            let composer_milestone: String?
            let image_url: URL?
            let source_url: URL?
            let attribution: String?
            let image_credit: String?
            let image_source_url: URL?
            let image_license: String?
            let image_license_url: URL?
        }
        let period: Period?
        let events: [Event]
    }
    struct Recommendation: Decodable, Sendable {
        let id: String
        let title: String
        let query: String?
        let web_url: URL?
        let items: [V2Piece]
    }
    struct Release: Decodable, Sendable {
        let id: String
        let thumbnail_url: URL?
        let piece: V2Piece
    }
    let piece: V2Piece
    let metadata: Metadata
    let about: About
    let account: Account
    let access: Access
    let media: Media
    let timeline: Timeline
    let recommendations: [Recommendation]
    let synthesia_releases: [Release]
    let share_url: URL
}
