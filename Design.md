You are a senior UI/UX designer and front-end engineer. Help me enhance the UI of my website.

## Context
- Website type: [e.g., school management system, admin dashboard, e-commerce, portfolio]
- Target users: [e.g., teachers and students, internal staff, mobile-first shoppers]
- Tech stack: [e.g., React + Tailwind, plain HTML/CSS/JS, Laravel Blade, Bootstrap 5]
- Current problems: [e.g., looks outdated, cluttered tables, inconsistent buttons, poor mobile layout]
- Brand: [colors, fonts, logo, tone: e.g., "professional and calm" or "playful and bold"]

## What I'm sharing
[Paste code, attach screenshots, or describe the pages: login, dashboard, forms, tables, etc.]

## Goals
1. Modern, clean, consistent visual design
2. Clear visual hierarchy so the most important actions stand out
3. Fully responsive (mobile, tablet, desktop)
4. Accessible (WCAG 2.1 AA: contrast, focus states, labels, keyboard navigation)
5. Faster-feeling interactions (loading states, feedback on actions)

## Visual Style & Modern Trends
Apply current UI trends tastefully. Choose what fits my site rather than stacking all of them:

- **Glassmorphism**: frosted-glass cards, modals, and navbars using backdrop-filter: blur(), semi-transparent backgrounds (rgba), subtle 1px light borders, and soft shadows over a colorful gradient or blurred background.
- **Soft gradients & mesh backgrounds**: smooth multi-color gradients or animated gradient blobs behind content.
- **Bento grid layouts**: dashboard or feature sections as asymmetric card grids of varying sizes.
- **Dark mode**: first-class dark theme with a toggle, not just inverted colors.
- **Neumorphism (light touch)**: soft inset/outset shadows only on select elements like toggles or stat cards.
- **Micro-interactions**: smooth hover lifts, button press feedback, animated icons, skeleton loaders, and page transitions (150–300ms, ease-out).
- **Bold typography**: large expressive headings, variable fonts (e.g., Inter, Plus Jakarta Sans, Geist, Satoshi), tight letter-spacing on titles.
- **Generous whitespace & rounded corners**: 12–24px radius, airy spacing, minimal clutter.
- **Subtle depth**: layered shadows, glow accents on primary actions, grain/noise texture overlays.
- **Modern icons**: consistent outline icon set (Lucide, Phosphor, or Heroicons).
- **Scroll animations**: fade/slide-in on scroll, kept subtle.

Requirements for these effects:
- Keep text readable on glass surfaces (check contrast; add a slight tint behind text if needed)
- Provide fallbacks for browsers without backdrop-filter support
- Respect prefers-reduced-motion for all animations
- Keep effects performant on mobile (limit heavy blur layers)
- Show me one sample component (e.g., a glass card + button + navbar) first so I can approve the style before you apply it everywhere

## Please do the following
1. **Audit**: List the top UI/UX issues you see, ranked by impact.
2. **Design system**: Propose a small, reusable system:
   - Color tokens (primary, secondary, neutral, success/warning/error, light + dark mode)
   - Typography scale (font family, sizes, weights, line heights)
   - Spacing scale (e.g., 4/8px grid), border radius, shadows
   - Core components: buttons, inputs, selects, cards, tables, modals, alerts, badges, nav/sidebar
3. **Layout**: Suggest improvements to page structure, navigation, and information grouping.
4. **States**: Define hover, focus, active, disabled, loading, empty, and error states.
5. **Implementation**: Provide updated code for [specific page/component] using my stack, with CSS variables or theme config so changes apply site-wide.
6. **Quick wins vs. bigger changes**: Separate changes I can make in under an hour from larger redesign work.

## Constraints
- Don't change functionality or backend logic, only the presentation layer
- Keep it lightweight (no heavy new libraries unless justified)
- Explain the reasoning behind each major design decision briefly