# Homepage artwork

## Current design — Many worlds (2026-10-26)

The approved reference is `/Users/arthur/Downloads/ChatGPT Image Oct 26, 2026, 10_35_30 PM.png`. The user requested its layout, colors and existing artwork, with the current header plus PianoLIT wordmark, current main-title size with tighter spacing, rounded iOS image corners, and initially Shop promoted ahead of Learn. Arthur subsequently restored the reference order: Web app, iOS app, Learn and Games are featured; Shop, Metronome and Blog are compact. The existing artwork is reused with its native image dimensions and CSS cropping; it does not use the rejected earlier image concepts.

Created with the built-in imagegen tool (no CLI/API fallback), using the supplied design as the edit reference to extract/reconstruct each standalone image and remove the overlaid webpage UI. Device screens remain decorative interpretations from that design, not live screenshots. Encoded with `cwebp -q 86`: Web app, iOS, Shop and Games files are 720×900; Learn, Metronome and Blog files are 960×640. These native dimensions are independent of the card’s current featured/compact role. HTML owns all titles, badges, descriptions, controls and rounded corners.

Final workspace assets: `resources/images/home/worlds/{webapp,ios,shop,games,learn,metronome,blog}.webp`. Mix copies them to matching `public/images/home/worlds/` paths and versions every file. Existing `editorial/` and SVG assets are retained.

### Current iOS backdrop — teal glass and daylight (2026-10-26)

Arthur found the neutral backdrop too plain and requested a more polished, interesting setting. Built-in imagegen replaced it with a teal wall, softly focused fluted glass and warm directional window light, retaining the phone composition and beige stone tabletop. The image remains distinct from the lavender HTML card; the full page remains white. No other hub artwork or layout changed.

Final asset: `resources/images/home/worlds/ios.webp`, copied to `public/images/home/worlds/ios.webp` and versioned by final-content MD5 in the Mix manifest. Encoded with `cwebp -q 86 -resize 720 900`. Generated source: `/Users/arthur/.codex/generated_images/01a17af7-96dc-75b3-8990-6bba66d15d9a/exec-114539e7-2e5d-48ba-a186-2afc01ff5a01.png`. Previous neutral asset: `/Users/arthur/.codex/visualizations/2026/10/27/01a17af7-96dc-75b3-8990-6bba66d15d9a/home-worlds/ios-before-teal.webp`.

Final edit prompt (built-in tool; no CLI/API fallback):

Use case: precise-object-edit. Asset type: realistic editorial product photograph for the PianoLIT homepage, portrait 4:5. Input image 1 is the edit target. Replace ONLY the dull taupe backdrop behind the phone with a polished, dimensional interior setting in rich muted teal and blue-green. Use softly out-of-focus vertical fluted glass on the right, a smooth teal wall on the left, and warm afternoon window light sweeping diagonally across those surfaces. The glass should have delicate believable refractions and a few soft highlights, not neon or glitter. Create depth through light, texture and a shallow depth of field; the overall feel is sophisticated, inviting product photography, with enough restrained detail to stay interesting at small card size. Keep clear color contrast with the light lavender #d8c1ff HTML card around the photograph. Preserve the exact existing phone, its position, angle, scale, proportions and dark curved frame. Preserve the screen as shown: forest cover, composer portrait, typography and labels, piano video thumbnail and blue play button. Keep the existing warm beige stone tabletop and grounded contact shadow; adjust reflected light subtly to fit the teal setting. Do not add foreground props, people, hands, plants, extra phones, webpage text, borders, logos or watermark. Do not redesign the screen. No flat solid-color backdrop, no abstract floating shapes, no dramatic dark void. Output only the edge-to-edge rectangular photograph; CSS supplies rounded corners.

### Earlier iOS backdrop contrast update (superseded)

Arthur requested more contrast between the iOS mockup photograph and its lavender card. The built-in imagegen tool edited only the photograph's lavender backdrop to warm medium stone-gray/taupe, retaining the phone composition, decorative screen and beige tabletop. The HTML card remains lavender with the existing rounded image window. No other hub artwork changed.

Final asset: `resources/images/home/worlds/ios.webp`, copied to `public/images/home/worlds/ios.webp` and reversioned with its final-content MD5 in the Mix manifest. Encoded with `cwebp -q 86 -resize 720 900` (78,134 bytes). Generated source: `/Users/arthur/.codex/generated_images/01a17af7-96dc-75b3-8990-6bba66d15d9a/exec-7fa2514f-839d-43c8-b156-b77847d6398b.png`. Previous artwork backup: `/Users/arthur/.codex/visualizations/2026/10/27/01a17af7-96dc-75b3-8990-6bba66d15d9a/home-worlds/ios-before-contrast.webp`.

Final edit prompt (built-in tool; no CLI/API fallback):

Use case: precise-object-edit. Asset type: PianoLIT homepage iOS mockup photograph, portrait 4:5. Input image 1 is the edit target. Change ONLY the lavender studio backdrop behind the phone to a warm, medium-toned stone-gray/taupe photographic backdrop (approximately #92897c), softly lit with a natural subtle tonal variation. The purpose is clear color and brightness contrast against the light lavender #d8c1ff HTML card surrounding this photograph, so its rounded upper corners are easy to see. Keep the backdrop warm neutral, not purple or pink, and not white. Preserve the exact existing phone, its position, size, angle, curved edges, dark frame, forest image, composer portrait, all screen typography and UI, piano thumbnail, and blue play button. Preserve the existing beige stone tabletop and realistic contact shadows, matching the new backdrop lighting naturally. Keep the same crop and composition. No new objects, plants, hands, people, patterns, webpage text, buttons, borders, or watermark. Output only the full rectangular photograph; the webpage supplies its rounded corners. Do not redesign or alter the phone screen. The ONLY requested visual change is the background color/environment tone.

### Initial prompt set

#### webapp

Generated source: `/Users/arthur/.codex/generated_images/01a17af7-96dc-75b3-8990-6bba66d15d9a/exec-7e434e31-2d9d-45df-bf1b-87b53dfabf37.png`

Use case: precise-object-edit. Input image is the user's APPROVED PianoLIT website design. Extract and clean up only the specified photograph/illustration from it into a standalone high-resolution image. Faithfully preserve that exact scene's subject, materials, colors, mood, angle and lighting. Do NOT invent an alternative concept. Remove all webpage headings, descriptions, badges, CTA buttons, URLs, arrows and outer borders from the extracted image, reconstructing the small areas they cover naturally. Output edge-to-edge art without rounded corners (CSS will round it). Do not add any people, hands or unrelated objects. Target: the laptop photograph in the FIRST LARGE lime card at upper left, below 'Web app'. Output portrait 4:5. Preserve the silver laptop displaying the existing PianoLIT Discover screen with the two orange pumpkins, sheet music/tabletop, cream mug at left and leafy background, same crop and natural light. The screen is part of the photograph and should stay as pictured; remove only the overlaid white 'Explore pieces' button and the little URL below it. Preserve the entire laptop screen; no lime header area.

#### ios

Generated source: `/Users/arthur/.codex/generated_images/01a17af7-96dc-75b3-8990-6bba66d15d9a/exec-d3504116-ed5f-4c81-93d6-f83aec89c501.png`

Use case: precise-object-edit. Input image is the user's APPROVED PianoLIT website design. Extract and clean up only the specified photograph/illustration from it into a standalone high-resolution image. Faithfully preserve that exact scene's subject, materials, colors, mood, angle and lighting. Do NOT invent an alternative concept. Remove all webpage headings, descriptions, badges, CTA buttons, URLs, arrows and outer borders from the extracted image, reconstructing the small areas they cover naturally. Output edge-to-edge art without rounded corners (CSS will round it). Do not add any people, hands or unrelated objects. Target: the tilted black iPhone photograph in the SECOND LARGE lavender card, below 'iOS app'. Output portrait 4:5. Preserve the phone's curved physical corners, angle, screen exactly as shown with '2. Intermezzo Op.118', forest cover, composer circle, tabs and piano video thumbnail. Preserve the lavender background and beige tabletop. Remove only webpage text/white 'Meet the app' overlay. No extra phone, no changed screen content. Whole scene fills the image.

#### shop

Generated source: `/Users/arthur/.codex/generated_images/01a17af7-96dc-75b3-8990-6bba66d15d9a/exec-4f056adc-ecb7-49ad-afbe-df2fd7341d26.png`

Use case: precise-object-edit. Input image is the user's APPROVED PianoLIT website design. Extract and clean up only the specified photograph/illustration from it into a standalone high-resolution image. Faithfully preserve that exact scene's subject, materials, colors, mood, angle and lighting. Do NOT invent an alternative concept. Remove all webpage headings, descriptions, badges, CTA buttons, URLs, arrows and outer borders from the extracted image, reconstructing the small areas they cover naturally. Output edge-to-edge art without rounded corners (CSS will round it). Do not add any people, hands or unrelated objects. Target: the sheet-music books and yellow pencil still life in the SMALL YELLOW SHOP card at bottom left. Reframe this SAME scene as portrait 4:5 so Shop can become a large card. Preserve the cream J. S. BACH score book, blue-gray clothbound books behind, yellow pencil on the diagonal, tactile warm paper, pale buttery yellow background and sunlight. Reveal a little more of those same books as needed to fill the taller photograph. No flowers or unrelated objects. No website title/caption/arrow button. Maintain the printed book-cover typography as part of the book.

#### games

Generated source: `/Users/arthur/.codex/generated_images/01a17af7-96dc-75b3-8990-6bba66d15d9a/exec-46912287-55ca-447a-ba4a-bd576261ae10.png`

Use case: precise-object-edit. Input image is the user's APPROVED PianoLIT website design. Extract and clean up only the specified photograph/illustration from it into a standalone high-resolution image. Faithfully preserve that exact scene's subject, materials, colors, mood, angle and lighting. Do NOT invent an alternative concept. Remove all webpage headings, descriptions, badges, CTA buttons, URLs, arrows and outer borders from the extracted image, reconstructing the small areas they cover naturally. Output edge-to-edge art without rounded corners (CSS will round it). Do not add any people, hands or unrelated objects. Target: the colorful piano-key sculpture in the FOURTH LARGE coral card, below 'Games'. Output portrait 4:5. Keep exactly the playful still life: oversized cream piano keys with black keys, wooden arch, coral sphere, cobalt-blue egg shape, upright lavender rounded block, pink-coral stage and soft directional shadows. Same arrangement and soft sophisticated material textures. No webpage header/text or 'Let's play' overlay. Frame the sculpture exactly like the source.

#### learn

Generated source: `/Users/arthur/.codex/generated_images/01a17af7-96dc-75b3-8990-6bba66d15d9a/exec-f11bc660-e85f-4a7e-8d9f-d3a492a97cb1.png`

Use case: precise-object-edit. Input image is the user's APPROVED PianoLIT website design. Extract and clean up only the specified photograph/illustration from it into a standalone high-resolution image. Faithfully preserve that exact scene's subject, materials, colors, mood, angle and lighting. Do NOT invent an alternative concept. Remove all webpage headings, descriptions, badges, CTA buttons, URLs, arrows and outer borders from the extracted image, reconstructing the small areas they cover naturally. Output edge-to-edge art without rounded corners (CSS will round it). Do not add any people, hands or unrelated objects. Target: the blue learning still life in the THIRD LARGE blue card, below 'Learn'. Output landscape 3:2. Preserve the open piano score above real piano keys, stack of books labeled CHOPIN DEBUSSY BACH, small cobalt-blue vase of delicate white flowers and powder-blue background, same natural sunlight and tactile paper. This becomes a small horizontal card, so arrange/crop this exact scene with the open book dominant in the lower right and the blue wall visible on the left. Remove all webpage headings, text and 'Start learning' button. No added objects.

#### metronome

Generated source: `/Users/arthur/.codex/generated_images/01a17af7-96dc-75b3-8990-6bba66d15d9a/exec-913a653c-f4e8-4be1-a522-0ca329d22dca.png`

Use case: precise-object-edit. Input image is the user's APPROVED PianoLIT website design. Extract and clean up only the specified photograph/illustration from it into a standalone high-resolution image. Faithfully preserve that exact scene's subject, materials, colors, mood, angle and lighting. Do NOT invent an alternative concept. Remove all webpage headings, descriptions, badges, CTA buttons, URLs, arrows and outer borders from the extracted image, reconstructing the small areas they cover naturally. Output edge-to-edge art without rounded corners (CSS will round it). Do not add any people, hands or unrelated objects. Target: the physical wooden metronome photograph in the SMALL MINT card at bottom center. Output landscape 3:2. Preserve the rich brown wood metronome, brass pendulum with sliding silver weight, pale mint-green backdrop and soft blurred green foliage at the right. Metronome stands toward the right half with natural pale mint breathing room on left for webpage text. Reconstruct full metronome height naturally if needed. Remove title, caption, white arrow button and all webpage UI.

#### blog

Generated source: `/Users/arthur/.codex/generated_images/01a17af7-96dc-75b3-8990-6bba66d15d9a/exec-8359b9ff-33b0-4aa4-807e-c7df1bfa69f4.png`

Use case: precise-object-edit. Input image is the user's APPROVED PianoLIT website design. Extract and clean up only the specified photograph/illustration from it into a standalone high-resolution image. Faithfully preserve that exact scene's subject, materials, colors, mood, angle and lighting. Do NOT invent an alternative concept. Remove all webpage headings, descriptions, badges, CTA buttons, URLs, arrows and outer borders from the extracted image, reconstructing the small areas they cover naturally. Output edge-to-edge art without rounded corners (CSS will round it). Do not add any people, hands or unrelated objects. Target: the open score, pen and headphones photograph in the SMALL PALE-PINK BLOG card at bottom right. Output landscape 3:2. Keep same open cream music pages, black pen on diagonal, black over-ear headphones at upper right, warm pale pink studio surface and beautiful soft shadows. Composition anchored right with pink breathing room at left. Remove webpage title/caption and white arrow circle only. No added objects, no different color palette.

## Earlier artwork history (superseded)

Updated 2026-10-22. Created with the built-in imagegen tool, then encoded as WebP with `cwebp` (quality 86 for cards, 90 with full alpha for the piano). No external service, API key, or stock image download was used.

Final editable project assets: `resources/images/home/editorial/{webapp,ios,learn,games,shop,blog,metronome,piano}.webp`. Mix copies them into `public/images/home/editorial/` and versions each entry. Cards are 720 × 960; the transparent piano is 1402 × 1122. The original generated PNGs remain in Codex's generated-images folder. Web app and iOS device screens now use real screenshot references, as recorded below. Shop uses the saved blue eScore cover and a T-shirt concept; the remaining images are decorative editorial interpretations.

The user's reference files were `6785809163f14a119e690598a3415e68.jpg` (composition, proportions, spacing, shadow, corner radius, neutral wall and confident colors) and `PianoLIT_ A World of Music.png` (the six product subjects). Actual HTML supplies every card title, description and link. Keep the piano image's natural aspect ratio and let the page continue beneath the first viewport instead of shrinking the instrument to fit.

## Original prompt set (card backgrounds superseded below)

### piano

Use case: product-mockup. Asset type: isolated architectural website foreground, true transparent background.
Create a realistic full-size upright acoustic piano and matching simple black piano bench, photographed absolutely straight-on at keyboard height in a quiet contemporary room. Satin ebony black cabinet with subtle wood grain, 88 realistically spaced keys, three brass pedals, normal upright piano dimensions (cabinet width about 150 cm and height about 120 cm). Piano bench in front, lower than keyboard. A small terracotta pot with a few elegant dark teal green leaves sits on the far right of the piano top, as a subtle accent. Soft daylight from upper left, tactile materials, natural soft contact shadows, sophisticated editorial interior photography. Entire instrument and bench visible with slight breathing room, not miniature, not cartoon, no squashing or exaggerated perspective. No room/background, no text, no brand names, no people, no decorative music notes. Background must be transparent, preserve natural semitransparent floor shadows. Landscape composition tightly framed around the furniture, do not add large empty top space. This will sit against a light grey plaster wall in a working website.

### webapp

Use case: product-mockup. Asset type: artwork for a sophisticated PianoLIT website project tile. Portrait image, 3:4 aspect ratio. Full image with no rounded frame or border. The TOP 28% MUST be completely empty warm-white (#faf9f6), reserved for actual HTML title and subtitle, no letters there. Below that is an elegant physical product still life in the manner of an art/design magazine: crisp materials, bright directional studio daylight, beautiful deep contact shadows, confident saturated color against warm white. Large subject fills the lower 72%, can crop at side and bottom edges. Avoid baby pastels, toy-like chunky shapes, floating dots, stars, circular backdrop disks, people, logos, watermarks, unnecessary text, cartoon style. Color should feel stimulating and refined, like vermilion-orange paint or deep ultramarine, not washed-out pastel.
Subject: an open elegant silver laptop at a slight three-quarter angle, screen upright, recognizably a piano discovery web app. On its clean white screen: small heading "Discover", a cobalt-blue featured music piece, three small photographic landscape tiles and a small row of composer portraits, understated interface. Laptop fills the lower section diagonally with its near keyboard corner partially cropped. It rests on an angular ultramarine-blue sculptural paper fold sweeping across the bottom, with a vivid cobalt highlight and deep ink-blue shadows. Laptop hardware realistic and slender, not inflated. Keep the screen simplified and clear.

### ios

Use case: product-mockup. Asset type: artwork for a sophisticated PianoLIT website project tile. Portrait image, 3:4 aspect ratio. Full image with no rounded frame or border. The TOP 28% MUST be completely empty warm-white (#faf9f6), reserved for actual HTML title and subtitle, no letters there. Below that is an elegant physical product still life in the manner of an art/design magazine: crisp materials, bright directional studio daylight, beautiful deep contact shadows, confident saturated color against warm white. Large subject fills the lower 72%, can crop at side and bottom edges. Avoid baby pastels, toy-like chunky shapes, floating dots, stars, circular backdrop disks, people, logos, watermarks, unnecessary text, cartoon style. Color should feel stimulating and refined, like vermilion-orange paint or deep ultramarine, not washed-out pastel.
Subject: a slim modern black iPhone nearly upright, slight natural tilt, positioned large in the lower section. The screen shows a sophisticated white and vivid cobalt piano music app: heading "Discover", one music feature panel, a few small composer portraits and tiny bottom navigation. Its top includes the realistic black camera cutout. Around the lower phone is folded deep indigo paper with a sharp vermilion-orange accent along one edge. Photographic glass/metal hardware, strongly contrasted indigo, white and warm orange, not purple pastel. No laptop, no extra device.

### learn

Use case: product-mockup. Asset type: artwork for a sophisticated PianoLIT website project tile. Portrait image, 3:4 aspect ratio. Full image with no rounded frame or border. The TOP 28% MUST be completely empty warm-white (#faf9f6), reserved for actual HTML title and subtitle, no letters there. Below that is an elegant physical product still life in the manner of an art/design magazine: crisp materials, bright directional studio daylight, beautiful deep contact shadows, confident saturated color against warm white. Large subject fills the lower 72%, can crop at side and bottom edges. Avoid baby pastels, toy-like chunky shapes, floating dots, stars, circular backdrop disks, people, logos, watermarks, unnecessary text, cartoon style. Color should feel stimulating and refined, like vermilion-orange paint or deep ultramarine, not washed-out pastel.
Subject: a tactile beautiful ivory circle-of-fifths wheel, tilted in perspective on deep emerald-green folded paper. The diagram is an editorial music-theory object, twelve precise divisions labeled around the ring C G D A E B F# C# Ab Eb Bb F; a single elegant dark musical note in the center. A slim off-white printed musical staff sheet extends diagonally beneath the wheel. Rich saturated emerald and teal green, dark forest shadows, fine black print on warm ivory. Refined geometry, no pencil, no cartoon icons, no floating decoration.

### games

Use case: product-mockup. Asset type: artwork for a sophisticated PianoLIT website project tile. Portrait image, 3:4 aspect ratio. Full image with no rounded frame or border. The TOP 28% MUST be completely empty warm-white (#faf9f6), reserved for actual HTML title and subtitle, no letters there. Below that is an elegant physical product still life in the manner of an art/design magazine: crisp materials, bright directional studio daylight, beautiful deep contact shadows, confident saturated color against warm white. Large subject fills the lower 72%, can crop at side and bottom edges. Avoid baby pastels, toy-like chunky shapes, floating dots, stars, circular backdrop disks, people, logos, watermarks, unnecessary text, cartoon style. Color should feel stimulating and refined, like vermilion-orange paint or deep ultramarine, not washed-out pastel.
Subject: a sleek white physical music-quiz panel tilted slightly, photographed as an elegant paper interface. Show a clear treble staff with a few black notes, short heading "Which note is this?", four small answer tiles C D E F, E highlighted in electric blue, and a short piano keyboard along the bottom with one cobalt-blue key. The panel sits over a sharply folded saffron-yellow paper sculpture, with deep golden-orange shadows and a subtle black accent. Saturated yellow and cobalt, sophisticated typography, minimal readable interface. No game controller, no toys, no cartoon stars.

### shop

Use case: product-mockup. Asset type: artwork for a sophisticated PianoLIT website project tile. Portrait image, 3:4 aspect ratio. Full image with no rounded frame or border. The TOP 28% MUST be completely empty warm-white (#faf9f6), reserved for actual HTML title and subtitle, no letters there. Below that is an elegant physical product still life in the manner of an art/design magazine: crisp materials, bright directional studio daylight, beautiful deep contact shadows, confident saturated color against warm white. Large subject fills the lower 72%, can crop at side and bottom edges. Avoid baby pastels, toy-like chunky shapes, floating dots, stars, circular backdrop disks, people, logos, watermarks, unnecessary text, cartoon style. Color should feel stimulating and refined, like vermilion-orange paint or deep ultramarine, not washed-out pastel.
Subject: a beautiful clothbound piano score book at a slight angle, spine and thick paper visible, resting against an open ivory music score with precise fine black staves. Book cover is vivid vermilion-orange and burgundy with quiet, elegant black typography "PianoLIT" and smaller "EDITIONS"; a large refined abstract black line motif. Cropped sheet music extends toward the right edge. Rich warm vermilion with deep wine-red shadows and warm ivory paper. Serious independent publishing aesthetic, no shopping bag, no cartoon musical note icon.

### blog

Use case: product-mockup. Asset type: artwork for a sophisticated PianoLIT website project tile. Portrait image, 3:4 aspect ratio. Full image with no rounded frame or border. The TOP 28% MUST be completely empty warm-white (#faf9f6), reserved for actual HTML title and subtitle, no letters there. Below that is an elegant physical product still life in the manner of an art/design magazine: crisp materials, bright directional studio daylight, beautiful deep contact shadows, confident saturated color against warm white. Large subject fills the lower 72%, can crop at side and bottom edges. Avoid baby pastels, toy-like chunky shapes, floating dots, stars, circular backdrop disks, people, logos, watermarks, unnecessary text, cartoon style. Color should feel stimulating and refined, like vermilion-orange paint or deep ultramarine, not washed-out pastel.
Subject: an elegant printed editorial article on thick off-white paper, slightly tilted with layered pages behind. Its upper image is a striking fine-art photograph of a dramatic dark mountain rising out of mist over a serene lake, cool charcoal and blue water with a tiny warm light. Below the photo a black heading reads "The art of listening" and a few thin charcoal text lines. The article floats just above a deep aubergine/plum folded paper shape with strong burgundy shadows. Mature architectural editorial still life, no pink pastel, no chat bubble or quotation icon.


## Metronome card (2026-10-22)

Arthur replaced the initial blue-screen preview with a physical metronome to match the other cards. Created using the built-in imagegen tool; original PNG: `/Users/arthur/.codex/generated_images/01a15fe1-9da0-7470-a73d-bb48091afabf/exec-90dece2c-4715-48f8-bc78-71fda9d763d0.png`. Final workspace asset: `resources/images/home/editorial/metronome.webp`, 720 × 960, encoded with `cwebp -q 86`. Mix copies and versions it using the same path convention as the other cards. The old preview SVG is removed.

Final generation prompt:

Use case: product-mockup.
Asset type: editorial artwork for a sophisticated PianoLIT website project card. Create one portrait image, exactly 3:4 aspect ratio (prefer 1080 by 1440). No card frame, rounded corners, UI, heading or watermark.
Primary request: a real physical mechanical metronome in the same refined, tactile, saturated editorial still-life style as the existing piano score-book and circle-of-fifths card illustrations.
Composition: The TOP 28% must be completely empty flat warm-white (#faf9f6), reserved for HTML copy. Below it, one handsome traditional pyramid-shaped wooden metronome, lid removed, photographed large at a slight three-quarter angle. Its top begins around 30% down the canvas; its broad base reaches near 90%. Keep the recognizable upper body, tempo scale and pendulum in the middle 30–80% so a website crop still clearly shows the instrument. No extra metronomes.
Subject details: rich dark walnut cabinet with real fine wood grain, ivory vertical tempo scale with fine dark ticks, slender polished brass pendulum arm and sliding brass weight, small winding key on the side. Precise believable instrument proportions, elegant angular silhouette, handcrafted quality. Only tiny incidental scale numbers, no prominent words or branding.
Scene/backdrop: beneath and behind the instrument in the lower section, sharply folded deep cobalt and ultramarine paper, vivid blue highlights and dark ink-blue shadows. The walnut and brass warmly contrast the blue. Large tactile subject, tightly composed like a design magazine product illustration.
Lighting/style: photorealistic editorial still life with a beautifully rendered illustrative polish; crisp physical materials, directional daylight from upper left, deep soft contact shadows, sophisticated texture and depth. Match the mature seriousness of the other card artwork. No pastel palette, cartoon/toy proportions, decorative circles, stars, floating notes, screens, app interfaces, people, logos, borders, or title text. Opaque image.

## Previous clean product artwork (2026-10-22)

Arthur retained the realistic mockups and requested removal of the surrounding abstract shapes. All seven card assets were edited with the built-in imagegen tool, then encoded at 720 × 960 using `cwebp -q 86`. The piano asset was not edited. The colored folded paper, unrelated props and fabric scenery are gone; the relevant products sit against a neutral warm-white surface with natural shadows. The blog article now contains a piano photograph so its subject is clearer. These remain decorative interpretations, not exact product screenshots or usable teaching materials. Keep abstract scenery out of future variants.

Previous WebPs are backed up outside the project at `/Users/arthur/.codex/visualizations/2026/10/22/pianolit-homepage-wall/artwork-before-cleanup/`.

Each edit used the corresponding previous card WebP as its sole edit target. The exact prompt for each image is the shared prompt below followed by its subject-specific paragraph.

### Shared edit prompt

Use case: precise-object-edit. Edit target: the attached existing PianoLIT homepage card artwork. Create one finished portrait image in the same 3:4 aspect ratio. The user likes the realistic product mockup but wants all confusing abstract shapes and unrelated props around it removed. Keep the main subject photorealistic, its recognizable design, approximate scale, location, camera angle and crisp material detail. Replace the surrounding colored folded-paper sculptures, geometric forms and unrelated props with one seamless neutral warm-white studio surface/background matching #faf9f6. Preserve subtle realistic soft contact shadows beneath the subject so it feels grounded. No colored blocks, decorative disks, triangles, fabric drapery, platforms, crystal spheres, ribbons, dramatic geometric shadows or background gradients. Keep the TOP 28% empty warm-white for the website HTML heading and caption; no new text in this area. Main subject occupies the lower 72%, large and legible. Natural daylight, mature premium product photography, dimensional and realistic, not pastel illustration, cartoon, toy-like or flat icon. Keep rich color within the relevant product itself, not in decoration. No frame, border, watermark or added card heading. Opaque background. Only edit the requested artwork; do not include a website screenshot.

### webapp

Main subject: the existing silver laptop showing a piano repertoire discovery web application. Preserve its realistic laptop hardware, perspective, screen layout, Discover heading, blue featured Clair de Lune panel, repertoire imagery and composer portraits. Remove ALL cobalt folded-paper scenery under and behind it. Let the laptop rest naturally on the plain warm-white surface with a soft shadow. Keep the screen recognizable as a piano discovery/library app, not a generic analytics dashboard.

Generated PNG: `/Users/arthur/.codex/generated_images/01a15fe1-9da0-7470-a73d-bb48091afabf/exec-e5327345-7aa7-4596-b654-3a022fe30475.png`.
Final asset: `resources/images/home/editorial/webapp.webp`.

### ios

Main subject: the existing black iPhone with its piano Discover app screen, piano feature image, composer portraits and bottom navigation. Preserve the realistic slim phone, slight tilt and screen composition. Remove ALL blue/orange folded paper, gold cylinder, glass sphere and stone block. Only the phone remains on the warm-white surface, with a natural soft grounding shadow. Do not add a stand or other device.

Generated PNG: `/Users/arthur/.codex/generated_images/01a15fe1-9da0-7470-a73d-bb48091afabf/exec-d3645a26-83e6-4814-817d-a9c7e9e029c3.png`.
Final asset: `resources/images/home/editorial/ios.webp`.

### learn

Main subjects: the existing ivory circle-of-fifths teaching wheel and its sheet-music page beneath it. Preserve realistic paper, dark legible musical markings and twelve divisions. The correct clockwise labels starting at the top are C, G, D, A, E, B, F#, Db, Ab, Eb, Bb, F. Remove ALL green paper sculpture, colored background and decorative folds. Keep only these meaningful music-theory learning objects, resting on the warm-white surface with soft shadows.

Generated PNG: `/Users/arthur/.codex/generated_images/01a15fe1-9da0-7470-a73d-bb48091afabf/exec-c0f7ac92-5126-463c-b69a-259f7a2b1782.png`.
Final asset: `resources/images/home/editorial/learn.webp`.

### games

Main subject: the existing realistic upright music-quiz panel with the heading "Which note is this?", treble staff, answer choices C D E F and small piano keyboard. Preserve its tactile mockup style, slight angle and blue selected-answer/key accents. Remove ALL yellow folded-paper scenery and black decorative forms. Keep only this recognizable interactive music-game mockup resting on a plain warm-white surface with a soft shadow. No controller or extra props.

Generated PNG: `/Users/arthur/.codex/generated_images/01a15fe1-9da0-7470-a73d-bb48091afabf/exec-58afb4c6-edaf-4637-88d4-4465573b9894.png`.
Final asset: `resources/images/home/editorial/games.webp`.

### shop

Main subjects: the existing vermilion clothbound PianoLIT EDITIONS music book and open printed piano score behind it. Preserve rich book-cover color, title, realistic cloth binding and printed music. Remove ALL burgundy fabric drapery and any abstract objects around the books; these two useful shop products should be the entire composition on a plain warm-white studio surface. Natural soft shadows, no extra props.

Generated PNG: `/Users/arthur/.codex/generated_images/01a15fe1-9da0-7470-a73d-bb48091afabf/exec-0cacdfee-80cd-472c-8118-388493046a97.png`.
Final asset: `resources/images/home/editorial/shop.webp`.

### blog

Main subject: the existing realistic printed editorial article with lightly layered pages behind it. Keep its article layout and title "The art of listening". To make it unmistakably a piano blog, replace ONLY the landscape photograph within the article with a refined close photograph of black acoustic piano keys and a pianist’s hands in soft natural daylight. Keep a few understated text lines below the title. Remove ALL surrounding burgundy/plum folded paper or geometric scenery. The article itself rests at the same slight angle on the warm-white surface with a soft natural shadow. No unrelated props.

Generated PNG: `/Users/arthur/.codex/generated_images/01a15fe1-9da0-7470-a73d-bb48091afabf/exec-725e4f81-66d1-4830-b58f-ee732a0228c3.png`.
Final asset: `resources/images/home/editorial/blog.webp`.

### metronome

Main subject: the existing handsome walnut mechanical metronome with ivory tempo scale, brass pendulum, sliding weight and winding key. Preserve its realistic proportions, fine wood grain, large centered placement and slight three-quarter angle. Remove ALL cobalt/ultramarine folded paper and blue surfaces. Only the wooden metronome remains, resting on the plain warm-white surface with a natural contact shadow. No screen, no added devices or decoration.

Generated PNG: `/Users/arthur/.codex/generated_images/01a15fe1-9da0-7470-a73d-bb48091afabf/exec-acd9667b-3bba-4001-99ed-53fa2f9514e1.png`.
Final asset: `resources/images/home/editorial/metronome.webp`.

## Fuller, higher-contrast artwork (2026-10-22; app screens and Shop superseded below)

Arthur supplied `Screenshot 2026-10-22 at 1.56.08 PM.png` as a reference for fuller cards, while asking for less generic imagery, stronger contrast and smaller gaps. These seven built-in imagegen edits supersede the smaller isolated-product versions above. Product close-ups fill the card width; PianoLIT branding, white/blue app interfaces, dark hardware, a green theory guide, red/navy score editions, a piano journal and walnut/brass metronome supply the contrast. No abstract backdrop shapes were restored. These are decorative interpretations rather than exact live interfaces, available publications or teaching diagrams.

The shared SCSS retains the card dimensions and positions the image 40px below the top, covering the remaining area with top-center alignment. Natural side/bottom crops are intentional. Gallery gaps are `clamp(20px, 2.2vw, 32px)` and 20px below 768px. Output WebPs remain 720 × 960, quality 86. Previous WebPs are backed up at `/Users/arthur/.codex/visualizations/2026/10/22/pianolit-homepage-wall/artwork-before-fuller-cards/`.

Each edit used its previous card WebP as image 1 and the supplied screenshot as image 2. Web app/iOS additionally used `resources/images/brand/app-icon.png` as image 3. Exact prompts follow: shared prompt plus the corresponding subject paragraph.

### Shared fuller-artwork prompt

Use case: product-mockup / precise-object-edit.
Asset: one realistic editorial product photograph for a PianoLIT homepage card, portrait 3:4 ratio, opaque image. Image 1 is the current subject to redesign. Image 2 is ONLY a reference for fuller close-up compositions across the card row, not a target to reproduce. Create ONLY the single requested card artwork without HTML titles, card frame, border or arrow. Do not create a collage or full website.
The user wants fuller images with more character and contrast, while retaining recognizable realistic mockups and NO abstract decorative shapes around them. Use an immersive close-up rather than a small object floating in empty space. The main product should dominate the LOWER TWO THIRDS edge to edge; natural side/bottom cropping is welcome, while its identifying content stays clear. Keep all main objects below the TOP 34%, which must remain empty neutral warm-white #faf9f6 for actual HTML text. Exactly the top 34% is the quiet copy area; do not add excess empty space below it. Fill the lower 66% with tactile, relevant product detail. Perspective and proportions must remain physically convincing, never squeezed.
Lighting: bold directional daylight, rich natural shadows, crisp blacks, bright ivory paper, deep walnut, precise metal/glass detail and confident saturated product color. High local contrast, sophisticated photographic realism. Avoid washed-out beige-on-beige catalog styling, pastel haze, generic landscapes used as decoration, inflated toy proportions or soft cartoon rendering. A simple real tabletop is allowed, but no ribbons, paper sculptures, floating geometric shapes, circles, decorative spheres, platforms or unrelated props. Transition naturally from the quiet upper area into the close-up product scene. Actual PianoLIT identity and subject-specific detail matter more than decoration.

### webapp — fuller composition

Subject: a realistic open silver laptop in a close three-quarter view, large screen filling nearly the whole lower width, with the black keyboard and metal edge crossing the bottom crop. It clearly shows the PianoLIT web app, not a generic music dashboard. Use small PianoLIT branding and the provided icon (image 3) in its top bar, bold clean sans-serif Discover heading, a blue Find your match pill, Free weekly pick with a deep cobalt music tile, and a row of recognizable classical composer portraits. The hero music tile uses an acoustic piano photograph and text Clair de Lune / Claude Debussy. White interface, charcoal type, vivid PianoLIT blue #0055fe navigation/play accents. Remove the generic oversized serif Discover and travel-gallery look of image 1. Crisp screen with intentional simplified hierarchy, not tiny illegible UI. Keep laptop bezel dark and realistic, full natural proportions. A subtle actual walnut tabletop can appear at the very bottom, no decorative backdrop shapes.

Generated PNG: `/Users/arthur/.codex/generated_images/01a15fe1-9da0-7470-a73d-bb48091afabf/exec-998ab194-da27-4a7d-85fe-d52e08a90ceb.png`.
Final asset: `resources/images/home/editorial/webapp.webp`.

### ios — fuller composition

Subject: one realistic black iPhone, larger and closer than image 1, screen turned toward camera with a slight diagonal tilt. It occupies about 65–75% of the image width in the lower region; lower edge may extend beyond the crop, but recognizable phone silhouette and piano app content must stay visible. On screen use the provided PianoLIT blue icon (image 3) and PianoLIT brand, strong clean sans-serif Discover heading, Free weekly pick with a rich blue piano performance tile, a row of circular composer portraits and the five bottom app tabs if visible. Interface is white, black and vivid brand blue #0055fe, with a tiny blue Find your match action. Remove the generic editorial-serif app styling. Show tangible black glass/metal, clear highlights and deeper grounding shadows. No extra phone, prop or abstract scenery. Keep main phone below the top 34% empty header zone.

Generated PNG: `/Users/arthur/.codex/generated_images/01a15fe1-9da0-7470-a73d-bb48091afabf/exec-a0c788c3-982c-4d77-957f-f41b3933b569.png`.
Final asset: `resources/images/home/editorial/ios.webp`.

### learn — fuller composition

Subject: a close-up real printed PianoLIT music-theory guide on ivory stock, leaning naturally over an open piano score. Replace the generic thick wheel token with an authentic useful reference-sheet/booklet mockup. Its top has small PianoLIT and bold Circle of Fifths typography. A large sharply printed circle-of-fifths diagram fills the page, with clean black lines, a dark emerald heading band and a strong cobalt highlight on C. Around its twelve sectors clockwise from the top: C, G, D, A, E, B, F#, Db, Ab, Eb, Bb, F. A slim black pencil resting along the edge is relevant and allowed. Close crop like the Learn card in image 2: the sheet reaches both sides and extends beneath the bottom edge, diagram remains identifiable. Fine black notation on the score behind it, rich shadows, dark green cloth spine if a backing booklet is visible. No ornamental paper shapes, generic floating wheel, big decorative music note or pastel wash.

Generated PNG: `/Users/arthur/.codex/generated_images/01a15fe1-9da0-7470-a73d-bb48091afabf/exec-23aa1dac-b1c4-4252-8ff2-d8c26d0c00b6.png`.
Final asset: `resources/images/home/editorial/learn.webp`.

### games — fuller composition

Subject: a realistic thin black tablet in landscape orientation, photographed close at a slight three-quarter angle, filling the width of the lower scene with some side cropping. Replace the paper-and-keyboard object with an unmistakable actual interactive PianoLIT music game on screen. White screen, small PianoLIT Games branding, a vivid cobalt progress bar and 3 / 10, bold Note Recognition heading, a properly drawn treble staff with one E note on the BOTTOM line, and four crisp answer buttons C D E F with E in vivid #0055fe blue. A slim keyboard row may appear below the answers if there is room; avoid extra decorative controls. Dark tablet bezel and black glass contrast strongly with the white UI and saturated blue. It rests on a real dark walnut tabletop, tightly cropped and naturally lit, not an abstract pedestal. Keep identifying game content within the visible lower region and the top 34% blank for HTML copy.

Generated PNG: `/Users/arthur/.codex/generated_images/01a15fe1-9da0-7470-a73d-bb48091afabf/exec-f156f7e6-c483-44d9-9662-4531eac631e5.png`.
Final asset: `resources/images/home/editorial/games.webp`.

### shop — fuller composition

Subject: a close-up small collection of real PianoLIT Editions piano score books, with one handsome vermilion red clothbound edition prominent in front and an open ivory score behind. Preserve the rich physical book texture from image 1, but replace abstract cover art with distinctive musical publishing typography: PianoLIT EDITIONS at top, large CHOPIN and smaller SELECTED PIANO WORKS. Fine gold-foil rules and a narrow printed musical-staff detail are allowed on the cover; no abstract cover circles. A navy spine and ivory page edges give sharp contrast behind the red book. Compose large and immersive, books extending to the side and bottom edges like the reference Shop card; no small floating isolated object. Keep identifiable title, binding and score notation visible. Sophisticated serious music editions, bright paper against black musical printing, rich red/navy, no generic landscape cover, no fabric drapery or unrelated objects.

Generated PNG: `/Users/arthur/.codex/generated_images/01a15fe1-9da0-7470-a73d-bb48091afabf/exec-992b1e46-96c0-4c85-bedd-f2bc30582042.png`.
Final asset: `resources/images/home/editorial/shop.webp`.

### blog — fuller composition

Subject: a close-up printed PianoLIT Journal article/open magazine, with a bold dark navy masthead PianoLIT JOURNAL and a distinctive editorial spread about playing piano. Title The art of listening in confident black typography, a few restrained body text columns and a large high-contrast photograph of a pianist’s hands on glossy black acoustic piano keys. One warm red editorial rule or small section label is acceptable as part of the printed layout. The white/ivory paper, dark photograph and ink provide strong contrast. Keep typography meaningful and intentional, not a generic motivational quote or landscape magazine. Angle the physical pages slightly with visible paper edges and natural deep shadows; fill the lower region edge to edge and crop some page edges as in image 2. No folded-paper background, geometric decoration or added props.

Generated PNG: `/Users/arthur/.codex/generated_images/01a15fe1-9da0-7470-a73d-bb48091afabf/exec-83d539bb-1682-4898-b85c-4601c7d6d83b.png`.
Final asset: `resources/images/home/editorial/blog.webp`.

### metronome — fuller composition

Subject: one serious physical mechanical metronome in a close-up three-quarter view, richly grained dark walnut cabinet, nearly black front recess, warm ivory tempo scale and sharp polished brass pendulum/weight. Larger and more immersive than image 1; fill most of the lower region width, with base naturally crossing the bottom edge if needed. Show the identifying pyramid top, scale and swinging arm clearly below the blank upper 34%. Engrave a subtle PianoLIT maker mark on the lower wood face. Tempo scale markings should be restrained and believable, not random double columns: 40, 60, 80, 100, 120, 160, 208. Strong but natural sidelight picks out brass and wood; real dark walnut or ebony piano-top surface beneath, no blue folded paper, abstract scenery or digital screen. Preserve normal real instrument proportions.

Generated PNG: `/Users/arthur/.codex/generated_images/01a15fe1-9da0-7470-a73d-bb48091afabf/exec-a57690d9-b82d-48a8-9630-6d0bcf84e6fc.png`.
Final asset: `resources/images/home/editorial/metronome.webp`.


## Actual web app and iOS screens (2026-10-22)

Arthur asked to replace the invented interfaces in the Web app and iOS artwork with the real products. The laptop screen uses a browser capture of https://my.pianolit.com/ taken at 1280 × 900 on 2026-10-22: Discover, Sonatina HWV 584 by G.F. Handel, the live composer row and bottom navigation. The iPhone screen uses Arthur's supplied first-screen screenshot, including its monochrome Discover hero, Sonatina Free Pick, composer portraits and five original tabs. These are screenshot-guided imagegen edits, not live embedded interfaces. Other product illustrations remain editorial interpretations.

Only these two artwork files changed. Device framing, blank copy area, tabletop and lighting retain the fuller composition. Output remains 720 × 960 WebP at quality 86, copied/versioned by Mix. Previous two WebPs are backed up at `/Users/arthur/.codex/visualizations/2026/10/22/pianolit-homepage-wall/artwork-before-real-screens/`.

### Exact shared screen-replacement prompt

Use case: precise-object-edit / product-mockup screen replacement.
Edit image 1, an existing portrait PianoLIT homepage card artwork. Image 2 is the REAL product screenshot and must be used as the screen content, faithfully preserving its actual text, imagery, layout, spacing, colors and navigation. Do not invent or redesign any part of the interface. This is a realistic device mockup with the actual screenshot on the screen, not an illustration of a hypothetical interface.
Keep the entire composition of image 1, device hardware, its size and angle, screen bezel, lighting, tabletop, scene, empty warm-white top copy area, image dimensions and crop unchanged. Replace ONLY the visible display inside the device with image 2. Map the supplied screenshot into the display's perspective, with natural rounded screen corners and very subtle realistic glass, keeping the screenshot sharply legible. Do not add other objects, surrounding shapes, title, watermark, card frame, border or arrow. Output one opaque portrait image, same 3:4 aspect ratio as image 1.

### webapp — actual screen

Image 1: `resources/images/home/editorial/webapp.webp` (prior version, backed up above).
Image 2: `/Users/arthur/.codex/visualizations/2026/10/22/pianolit-homepage-wall/webapp-live-discover-2026-10-22.png`.

The device is a silver laptop. Remove its invented blue Clair de Lune interface completely and insert the supplied ACTUAL white Discover page: small blue PianoLIT icon at upper left and Sign in upper right; centered Discover heading, subtitle and white Find your match button; Free weekly pick label; wide BLACK Sonatina / HWV 584 · G.F. Handel card with smoky artwork, red Late intermediate dot and small media labels; Composers row with the same circular photographs; fixed white bottom navigation Discover, Explore, Collections, My pieces, Settings. All screenshot proportions and content relationships should remain faithful; scale the screenshot as a whole. Keep the silver laptop and black bezel exactly where they are.

Generated PNG: `/Users/arthur/.codex/generated_images/01a15fe1-9da0-7470-a73d-bb48091afabf/exec-36384790-2106-40d1-b4af-338f7999a7b1.png`.
Final asset: `resources/images/home/editorial/webapp.webp`.

### ios — actual screen

Image 1: `resources/images/home/editorial/ios.webp` (prior version, backed up above).
Image 2: `/Users/arthur/Downloads/Screenshot 2026-10-22 at 2.01.44 PM.png`.

The device is a black iPhone. Remove the invented white/blue Moonlight Sonata interface completely and insert image 2: grayscale hands playing piano hero, large white Discover, white subtitle Take a quick tour to find the perfect piece for you, outlined FIND YOUR MATCH button, curved white boundary below hero; Free weekly pick label; black smoky artwork card Sonatina HWV 584 / G.F. Handel / red dot LATE INTERMEDIATE; Composers with View all and the SAME three composer photographs as screenshot; white five-tab bar Discover, Explore, Playlists, My Pieces, Settings. Retain the actual screenshot's status bar, white/black/blue colors, images, and every interface section. Fit the WHOLE screenshot within the phone display, including bottom tabs; never crop away the bottom navigation. Keep the physical phone frame, tilt and scene unchanged; its camera cutout may overlay the screenshot naturally.

Generated PNG: `/Users/arthur/.codex/generated_images/01a15fe1-9da0-7470-a73d-bb48091afabf/exec-8b89179c-76f9-4ec3-8909-a32b56f22b8d.png`.
Final asset: `resources/images/home/editorial/ios.webp`.

## Shop eScore and T-shirt (2026-10-22)

Arthur requested the existing eScore mockup in place of the invented red Chopin edition, plus a T-shirt to represent planned merchandise. Located the saved product mockup at `storage/app/public/escore/mockup_image/pianolit-test-mockup-2942.jpg`: the blue **PIANO SOLOS — Book 1** cover. The new artwork uses that cover alongside a black crew-neck PianoLIT T-shirt. The shirt is an illustrative merchandise concept, not a claim that this exact design is already on sale.

Created with the built-in imagegen tool; no CLI fallback. Only `resources/images/home/editorial/shop.webp` and its matching public copy/version were replaced. Output is 720 × 960 WebP, quality 86. The previous Shop asset is backed up at `/Users/arthur/.codex/visualizations/2026/10/22/pianolit-homepage-wall/artwork-before-shop-products/shop.webp`.

Inputs: prior Shop artwork (edit target), the saved eScore mockup above (actual cover reference), and `resources/images/brand/app-icon.png` (shirt branding reference).
Generated PNG: `/Users/arthur/.codex/generated_images/01a15fe1-9da0-7470-a73d-bb48091afabf/exec-4bbb0f16-0a0c-45ac-9c45-5036e3f61625.png`.

### Exact Shop edit prompt

Use case: compositing / precise-object-edit.
Asset type: realistic editorial product photograph for the existing PianoLIT Shop homepage card, portrait 3:4 ratio, opaque.
Input images: image 1 is the existing Shop artwork to edit; image 2 is the ACTUAL saved PianoLIT eScore mockup, whose blue cover must be faithfully retained; image 3 is the actual PianoLIT brand icon, a supporting branding reference for the shirt.
Primary request: replace the invented red Chopin edition with the real blue eScore from image 2, and include a realistic cotton T-shirt as an equally recognizable second shop product. The shirt is a simple black crew-neck with a modest small print of the exact PianoLIT icon from image 3 on the chest, without slogans. Arrange it naturally on the table with collar and short sleeves visibly identifying it as a T-shirt; its lower portion may be neatly folded. Do not make it look like a shapeless cloth or just a book backdrop.
Show the actual blue eScore as a thin physical softcover score/booklet mockup in perspective, using image 2 as the exact printed cover: PIANO SOLOS, Book 1, For Late Elementary to Beginner levels, PianoLIT eScore, large subtle numeral 1. Match the real typography, blue color, horizontal rules and layout. No red Chopin book and no invented score edition. A little open printed score beneath is allowed as in the original.
Preserve the current artwork's confident realistic photography, natural daylight, rich shadows, warm-white top area and tightly filled lower scene. Keep the TOP 30% empty warm-white #faf9f6 for the actual HTML card copy. Both identifiable product subjects must occupy the lower 70%, filling the width with natural edge cropping, and their key features must remain within the middle 35–85% of the image so the homepage crop still shows the blue book title and shirt collar/chest clearly. Rich blue cover, charcoal black fabric, ivory music paper and natural wood provide the contrast. Keep believable scale/proportions, tactile paper and cotton detail. No abstract shapes, ornaments, extra products, people, watermarks, card border, arrow or added headline. Output the single Shop artwork only.

## Softer background piano, without plant (2026-10-22)

Arthur requested removal of the plant, a 60px downward move and a less realistic piano that recedes behind the cards. Built-in imagegen edited the existing transparent artwork into a simplified matte-gray architectural illustration, retaining the upright, bench, proportions, keyboard and contact positions. The pot/plant are gone; dark photographic textures, bright gold and heavy highlights are subdued. Output is 1402 × 1122 WebP, quality 90 with full alpha quality. The original image is backed up at `/Users/arthur/.codex/visualizations/2026/10/22/pianolit-homepage-wall/artwork-before-soft-piano/piano.webp`.

Final asset: `resources/images/home/editorial/piano.webp` (copied/versioned in `public/images/home/editorial/`). Generated PNG: `/Users/arthur/.codex/generated_images/01a15fe1-9da0-7470-a73d-bb48091afabf/exec-2e2fd579-19fd-441e-b921-5351a3eaf4a2.png`.

The layout uses `--room-offset: 60px` in `resources/sass/pages/_home.scss`, added to the existing desktop/mobile room margins, so the floor and following content move with the piano. Its CSS drop shadow is also lighter. All project-card artwork and interactions remain unchanged.

### Exact piano edit prompt

Use case: style-transfer / precise-object-edit.
Asset type: transparent decorative background artwork for the PianoLIT homepage.
Edit the supplied upright piano and bench image. Remove the plant and terracotta pot completely, leaving a bare piano top. Change the piano and bench from photorealistic product photography into a restrained, sophisticated architectural illustration that quietly belongs to the page background. It must no longer look like the main hero product.
Use simplified broad matte surfaces, soft tonal modeling, subtle precise edges and very little material texture. Neutral medium-light cool gray/slate cabinet and bench, roughly #949aa1 for the main surfaces with restrained darker #747c85 shadows; off-white and muted slate keys, subdued pale pewter pedal/trim details instead of bright gold. Keep good recognition as an acoustic upright piano, but reduce photographic grain, tiny details, glossy highlights, specular shine, deep black areas and dramatic shadows. Do not use solid black cabinetry. Elegant editorial illustration, mature and quiet, not cartoon, not toy-like, not pastel candy, not outlined clipart and not watercolor splashes. Gentle soft ambient contact shadow only.
CRITICAL invariants: keep the exact straight-on camera, natural full-size upright proportions, keyboard geometry, matching bench, three pedals and overall scale/placement from the reference. Use exactly the same landscape canvas aspect ratio 1402:1122. Leave the transparent space above the cabinet where the plant used to be; do NOT crop more tightly or move/resize the piano after removing the plant. Cabinet top remains around 15% of canvas height, rear cabinet floor contact around 82%, bench feet around 95%. Retain the entire piano and bench silhouette with room for subtle contact shadow.
Background must be truly transparent, including the empty area around and above the piano. Do not add a wall, floor, gradient background, text, logo, branding, score, lamp, plant, props, people or border. The website supplies its own light gray wall and floor.

## Black piano and soft room daylight (2026-10-22)

Arthur clarified that the piano must remain naturally black, with only less surface detail, and supplied `/Users/arthur/Downloads/PianoLIT_ Music Beyond the Notes.png` as a reference for diagonal window light and a darker right side. This supersedes the gray finish above. Built-in imagegen restored black satin cabinet/bench surfaces, restrained texture and subtle brass, keeping the bare top, natural proportions and existing transparent canvas. Final asset: `resources/images/home/editorial/piano.webp`, 1402 × 1122, quality 90/full-quality alpha; matching public output/version are built through Mix.

Generated PNG: `/Users/arthur/.codex/generated_images/01a15fe1-9da0-7470-a73d-bb48091afabf/exec-b8377dbb-754c-48c8-afa4-b17963300847.png`.
Previous gray asset and SCSS backup: `/Users/arthur/.codex/visualizations/2026/10/22/pianolit-homepage-wall/artwork-before-black-piano-light/`.

Room lighting is code-native: `resources/sass/pages/_home.scss` keeps the shared wall color as the base, with transparent left glow/right shading, feathered diagonal window-light bands, and a translucent floor gradient. Lighting is decorative, static and behind content. The existing 60px scene offset and all card/header/footer content remain unchanged.

### Exact black-piano edit prompt

Use case: precise-object-edit / style-transfer.
Edit image 1, the isolated upright piano and bench, keeping a truly transparent background. Image 2 is ONLY a reference for the normal BLACK piano finish and restrained room atmosphere, not a layout to reproduce.
The user rejected the gray piano. Restore a natural BLACK satin ebony cabinet and matching BLACK bench, with only slightly less detail than a realistic piano. The main surfaces must read as black (#17191c to #292c30), NOT gray paint, silver, white, faded, low-opacity or washed out. Retain white/black keys and subtle muted brass trim/pedals. Simplify wood grain, leather grain, tiny seams and excessive reflections, using smooth matte-to-satin planes and quiet restrained highlights. A tasteful lightly stylized editorial architectural render, not a photoreal close-up and not a toy/cartoon. Soft daylight from upper left with slightly darker right-facing surfaces. Keep the black identity while reducing surface noise.
Preserve EXACTLY image 1's natural proportions, straight-on camera, upright piano silhouette, empty score rack, matching bench, keyboard and three pedals. Keep the entire instrument's scale and position on the same 1402:1122 landscape canvas: cabinet top at about 15%, rear cabinet floor contact about 82%, bench feet about 95%. Do not crop more tightly or shift the instrument. Retain only subtle transparent contact shadows.
The plant stays removed and the top stays bare. No score, decorative props, brand letters, wall, floor, rug, lighting beams, scenery, text or logo. The website will supply its own wall lighting separately. Output only the black upright and black bench on genuine transparency, matching the original canvas and empty margins.

## Grounding the piano in the room (2026-10-22)

Arthur requested realistic shadows be restored so the simplified black piano feels physically present in the left-lit room. The piano bitmap is unchanged. `resources/sass/pages/_home.scss` now adds percentage-positioned contact shadows under the wheels and bench feet, broader ambient floor shadows and a soft right/down silhouette shadow. Read-only alpha inspection of the current 1402 × 1122 image placed wheel contacts at 88.8% height and front bench feet at 94.6%; the gradients scale with the image on mobile. Keep these floor-contact shadows when simplifying the artwork. No new imagegen call or raster modification was needed for this CSS scene update.
