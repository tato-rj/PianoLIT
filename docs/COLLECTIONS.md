# Web app Collections

The web app's `/playlists` page is now titled **Collections**. Its route name remains `webapp.playlists`; existing `webapp.playlists.show` links still open the same playlist/piece pages. The iOS API routes, controllers, shared API builder, playlist model, stored covers, group values, and piece lists are unchanged.

## Editorial controls

Continue editing playlist names, subtitles, descriptions and piece selections in the existing administration. `config/collections.php` supplies **web-only** presentation:

- `featured`: preferred playlist name normalized as a slug (apostrophes removed). If unavailable, the first eligible playlist in the stored order is featured. The label is **Featured collection**, with no promised automatic refresh.
- `inspiration`: ordered names for the three editorial suggestions. Only existing eligible playlists appear. If none match, the page uses up to three other eligible collections. Mockup-only titles and difficulty claims were not added to real playlists.
- `artwork`: mapping of normalized playlist names to local illustrations and optional browse categories (`mood`, `composer`, `level`, or `other`). Unknown names retain their existing uploaded cover; missing/failed covers use the featured artwork. Only categories with content get filter buttons. All eligible collections remain accessible under All, including `other`.
- `books`: eight Piano Solos announcements using the supplied cover colors and level descriptions. They are **Coming soon**, not links to empty books. There are no invented selections, progress indicators, purchase links, or automatic book curation. The books occupy one horizontal row with native touch/trackpad scrolling and keyboard access. Previous/next buttons move one card, disable at the ends, hide when everything fits, and respect reduced-motion preferences. Without JavaScript the native scrolling row remains usable.

Changing a playlist name can require updating its presentation mapping. Configuration changes require refreshing the config cache during deployment if it is enabled.

The top callout links to the book announcements. A real “Continue” card should only be added once there is authenticated, account-owned book progress. When the books are ready, add dedicated curated book data and detail pages with ordered piece selections and teaching notes; shop links and automated collection generation remain future work.

The existing eligibility rule is retained: more than five linked pieces, of which at least five have tutorials. Counts show pieces with tutorials, matching the existing web detail page and mobile catalog. The web service reads this with a single query and never calls the shared API ordering routine (which can write order values). There is no new shared or personalized cache.

## Assets and build

Fourteen illustrations generated with the **built-in image-generation tool** live in `public/images/webapp/collections/`. The original nine used the approved full-page mockup as the visual reference; Books 4–8 used Book 3's illustration as the style reference and the supplied cover colors. Text and book numerals are rendered in Blade/CSS, not baked into artwork. The hero is 1200 × 800; the other thirteen are 900 × 600 WebP. The combined artwork is approximately 619 KB. No runtime generation service, API key, recurring charge, or migration is required for this static version.

Edit `resources/sass/views/_collections.scss` and `resources/js/views/collections.js`, run the existing production build, and ship the relevant `public/css/app.css`, `public/js/views/collections.js`, and manifest entries together. The page uses the existing logo, Poppins headings, system body font, guest sign-in action, and fixed bottom menu. The web menu label changes to Collections throughout the web app.

## Generation prompts

The original nine requests referenced the approved Collections mockup; the five additional books referenced `book-3.webp` for visual consistency. Original generated PNGs remain in the chat's generated-images directory; the checked-in WebP files are the deployable assets. These are the final prompts used with the built-in tool:

### featured.webp

Create ONE standalone landscape editorial artwork asset for the PianoLit website, approx 1536x1024 landscape. Reference image is approved UI concept: reproduce the VISUAL STYLE and subject of the featured hero illustration ONLY, no website interface. Abstract flowing navy blue and dusty indigo hills merging into elegant ivory piano keys, large peach sun toward upper right, muted lilac arch in background, tiny dark botanical sprig at far right. Ivory warm paper backdrop, refined subtle paper-cut/gouache texture, premium calming classical-music book illustration. Composition balanced across entire frame, with simpler left half so it crops well. No text, lettering, numbers, logos, borders, buttons, UI or watermarks. Entire rectangular image filled edge to edge. This is an individual website artwork, not a screenshot.

### book-1.webp

Generate a single standalone landscape artwork for PianoLit Piano Solos BOOK ONE, inspired by the BLUE number 1 card in the reference. No UI, no frame, no text or numerals (we overlay these in code). Vivid cyan and royal blue tonal palette, delicate sheet music pages unfurling diagonally across curved piano keys from bottom left, gently flowing abstract blue hills, refined paper-cut gouache illustration, subtle paper grain. Keep upper left and right half uncluttered and tonal blue for overlay title and large 1. Edge-to-edge 3:2 landscape. Cohesive adult classical music book series, restrained sophisticated shapes, no photorealism, no watermark.

### book-2.webp

Single standalone 3:2 landscape illustration for PianoLit Piano Solos BOOK TWO. Match the teal number 2 card artwork in reference mockup, but NO text, NO numerals, NO UI. Deep teal and turquoise monochrome palette, elegant layered arches and gently curling sheet-music pages in lower left, subtle abstract sweeping lines along lower edge. Refined textured paper-cut gouache illustration, sophisticated classical repertoire visual. Right half fairly open teal space for large number overlaid separately. Full bleed rectangular art, no borders, watermarks or logos, no physical book mockup. Cohesive style with reference.

### book-3.webp

Single standalone 3:2 landscape illustration for PianoLit Piano Solos BOOK THREE. Match the green number 3 card artwork in reference mockup, but NO text, NO numerals, NO UI. Rich emerald and forest green with lighter sage tonal accents. Elegant abstract small stairs ascending from lower left toward middle, graceful dark botanical leaves along left edge, layered rounded green hills. Refined textured paper-cut gouache, sophisticated classical music collection. Leave right half calm green open field for separate large number overlay. Full bleed landscape, no frames, no watermark or logo. Not a physical book rendering.

### night.webp

ONE standalone 3:2 landscape editorial cover illustration for PianoLit 'At night' and lullaby repertoire, matching the dark navy moonlit cover in reference screenshot. Full bleed, no UI, text, lettering or logos. Cream crescent moon in upper right over layered midnight navy and dusty-blue hills and quiet reflective water, fine subtle paper grain and gouache. Sparse sophisticated composition, gentle dreamy classical music mood. Left upper region relatively open dark navy for separate white title. No photorealism, no frames.

### melody.webp

ONE standalone full bleed 3:2 landscape editorial illustration matching the sage 'Bring out the melody' cover of reference. No text or UI or numbers. Warm ivory paper center, dark forest green sculptural leaves at left and right edges, five graceful fine undulating parallel lines flow across lower half like a melodic musical staff. Sage green and cream paper-cut gouache, subtle paper grain, refined serene adult classical music aesthetic. Upper central ivory region kept simple for separate title overlay. Crisp sophisticated composition, no watermarks, no frame.

### steps.webp

ONE standalone 3:2 landscape editorial cover illustration matching terracotta 'Return to the piano' in reference. No text, numbers, UI, logo or border. Warm terracotta, muted peach, ivory and soft brown. Sculptural abstract staircase made of ivory piano keys rises from lower center to an arched doorway toward upper right. Simple layered warm architectural shapes, soft paper grain, gouache and cut-paper sophistication. Spacious left side for later text overlay. Full bleed, adult classical music editorial artwork.

### botanical.webp

ONE standalone 3:2 landscape artwork for PianoLit 'Hidden gems', matching botanical Hidden gems card of reference. No text, numbers, UI, logo or border. Deep forest-green background, graceful oversized dark sage and muted golden ochre leaves sprouting from lower left, cream rounded paper-cut curve toward lower right. Refined layered gouache and cut-paper texture, subtle paper grain, elegant warm editorial style for adult classical music repertoire. Upper right dark green space left relatively simple for title overlay. Full bleed.

### morning.webp

ONE standalone 3:2 landscape illustration matching Sunday morning cover in reference. No words, UI, logo or border. Quiet warm ivory room, soft morning sunshine through a simple window with translucent cream curtains, small sage ceramic coffee cup at lower right on light wooden table, elegant leafy plant at lower left. Soft architectural shadows, subtle gouache brush texture and tactile paper-cut style. Sophisticated calm adult classical music editorial artwork, full bleed. Keep center upper area uncluttered for separate title overlay. Not a photograph.

### book-4.webp

Use case: stylized-concept. Create a standalone landscape 3:2 editorial illustration for PianoLIT Piano Solos Book 4. Reference image is the existing Book 3 illustration: match its refined paper-cut gouache shapes and subtle paper texture. New scene: sunlit terracotta arches and a rising curved stairway, with a small abstract piano-key motif at lower left. Palette inspired by Book 4 cover: rich orange, amber, burnt sienna, warm cream highlights. Full bleed, calm elegant geometry, musical discovery mood. Keep top left and lower right visually quiet for HTML title and large numeral overlays. No text, letters, numbers, logos, border or mockup. This is one artwork, not a page.

### book-5.webp

Use case: stylized-concept. Create a standalone landscape 3:2 editorial illustration for PianoLIT Piano Solos Book 5. Reference image is the existing Book 3 illustration: match its refined paper-cut gouache shapes and subtle paper texture. New scene: a stylized grand piano silhouette on the left in a warm recital room, sweeping curtain-like curved shapes behind it and a small cream sheet of music. Palette inspired by Book 5 cover: deep vermilion red, crimson, burgundy, restrained peach and warm cream highlights. Full bleed, calm elegant geometry, musical mastery mood. Keep top left and lower right visually quiet for HTML title and large numeral overlays. No text, letters, numbers, logos, border or mockup. This is one artwork, not a page.

### book-6.webp

Use case: stylized-concept. Create a standalone landscape 3:2 editorial illustration for PianoLIT Piano Solos Book 6. Reference image is the existing Book 3 illustration: match its refined paper-cut gouache shapes and subtle paper texture. New scene: layered architectural arches opening onto a violet evening sky, with a flowing ribbon of ivory piano keys rising on the left. Palette inspired by Book 6 cover: rich magenta, plum, mulberry, muted lilac, restrained ivory highlights. Full bleed, calm elegant geometry, poetic musical discovery mood. Keep top left and lower right visually quiet for HTML title and large numeral overlays. No text, letters, numbers, logos, border or mockup. This is one artwork, not a page.

### book-7.webp

Use case: stylized-concept. Create a standalone landscape 3:2 editorial illustration for PianoLIT Piano Solos Book 7. Reference image is the existing Book 3 illustration: match its refined paper-cut gouache shapes and subtle paper texture. New scene: sculptural olive leaves on the left surrounding an open cream music score on a low geometric plinth, softly layered rolling hills behind. Palette inspired by Book 7 cover: olive yellow-green, moss, dark olive, muted golden chartreuse and warm ivory highlights. Full bleed, calm elegant geometry, contemplative musical discovery mood. Keep top left and lower right visually quiet for HTML title and large numeral overlays. No text, letters, numbers, logos, border or mockup. This is one artwork, not a page.

### book-8.webp

Use case: stylized-concept. Create a standalone landscape 3:2 editorial illustration for PianoLIT Piano Solos Book 8. Reference image is the existing Book 3 illustration: match its refined paper-cut gouache shapes and subtle paper texture. New scene: a dark walnut grand piano on the left beneath a tall golden arch, a broad curved stairway leading into warm light, restrained botanical silhouette at far left. Palette inspired by Book 8 cover: ochre brown, walnut, copper, amber and warm cream highlights. Full bleed, calm elegant geometry, accomplished musical journey mood. Keep top left and lower right visually quiet for HTML title and large numeral overlays. No text, letters, numbers, logos, border or mockup. This is one artwork, not a page.
