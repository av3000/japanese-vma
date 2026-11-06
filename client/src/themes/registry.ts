/**
 * Theme registry: the manifest plus the slots that import assets (STYLING-03, #498).
 *
 * Components read a slot through `useTheme()` (`theme.icons.likeOn`, `theme.hero`), never the theme id.
 * What must differ in markup is a slot here; what must differ in behaviour is a named flag, never a
 * theme name (`repo-theme-slots-not-names`).
 */
import band640Jpg from '@/assets/images/home/fuji-band-640.jpg';
import band640Webp from '@/assets/images/home/fuji-band-640.webp';
import band1280Jpg from '@/assets/images/home/fuji-band-1280.jpg';
import band1280Webp from '@/assets/images/home/fuji-band-1280.webp';
import band1920Jpg from '@/assets/images/home/fuji-band-1920.jpg';
import band1920Webp from '@/assets/images/home/fuji-band-1920.webp';
import sakuraHero from '@/assets/images/themes/sakura/hero.svg';
import sakuraMark from '@/assets/images/themes/sakura/mark.svg';
import sumiHero from '@/assets/images/themes/sumi/hero.svg';
import sumiMark from '@/assets/images/themes/sumi/mark.svg';
import type { IconName } from '@/components/shared/Icon';
import { type ThemeId, type ThemeManifestEntry, themeManifest } from './manifest';

/** A mark drawn before the "JPLearning" wordmark. Decorative: the visible text names the link. */
export interface ThemeLogo {
	src: string;
	width: number;
	height: number;
}

/** The homepage band image. `src` is the fallback; `sources` are tried first. */
export interface ThemeHero {
	sources: { type: string; srcSet: string }[];
	src: string;
	srcSet?: string;
	width: number;
	height: number;
	/** Shown on the image when the artwork needs one. */
	credit: { label: string; href: string } | null;
}

export interface ThemeIcons {
	/** Shown by the scheme toggle while the light scheme is on screen. */
	schemeLight: IconName;
	/** Shown by the scheme toggle while the dark scheme is on screen. */
	schemeDark: IconName;
	likeOff: IconName;
	likeOn: IconName;
}

export interface ThemeConfig extends ThemeManifestEntry {
	/**
	 * Loads the theme's own web fonts. Called when the theme is chosen, so their CSS is a separate chunk
	 * that other themes never download (the font's @font-face rules alone are ~60 kB gzipped).
	 */
	loadFonts?: () => Promise<unknown>;
	/** Null: the wordmark alone. */
	logo: ThemeLogo | null;
	hero: ThemeHero;
	icons: ThemeIcons;
}

const kinari: ThemeConfig = {
	...themeManifest.kinari,
	logo: null,
	hero: {
		sources: [{ type: 'image/webp', srcSet: `${band640Webp} 640w, ${band1280Webp} 1280w, ${band1920Webp} 1920w` }],
		src: band1280Jpg,
		srcSet: `${band640Jpg} 640w, ${band1280Jpg} 1280w, ${band1920Jpg} 1920w`,
		width: 1920,
		height: 384,
		credit: {
			label: 'Photo: Ningyu · Unsplash',
			href: 'https://unsplash.com/photos/mount-fuji-peak-in-black-and-white-tb49PTdW1ZM',
		},
	},
	icons: { schemeLight: 'sunSolid', schemeDark: 'moonSolid', likeOff: 'thumbsUpRegular', likeOn: 'thumbsUpSolid' },
};

/** Sakura (桜): spring. A blossom mark and band, a rounded Japanese face, blossom like icons. */
const sakura: ThemeConfig = {
	...themeManifest.sakura,
	loadFonts: () =>
		Promise.all([import('@fontsource/zen-maru-gothic/400.css'), import('@fontsource/zen-maru-gothic/700.css')]),
	logo: { src: sakuraMark, width: 640, height: 640 },
	hero: { sources: [], src: sakuraHero, width: 1920, height: 384, credit: null },
	icons: {
		schemeLight: 'sakuraSunSolid',
		schemeDark: 'sakuraMoonSolid',
		likeOff: 'blossomRegular',
		likeOn: 'blossomSolid',
	},
};

/** Sumi (墨): ink painting, dark only. An ensō mark and an ink-wash band; the scheme toggle is hidden. */
const sumi: ThemeConfig = {
	...themeManifest.sumi,
	logo: { src: sumiMark, width: 640, height: 640 },
	hero: { sources: [], src: sumiHero, width: 1920, height: 384, credit: null },
	icons: { schemeLight: 'sunSolid', schemeDark: 'moonSolid', likeOff: 'thumbsUpRegular', likeOn: 'thumbsUpSolid' },
};

export const themeRegistry: Record<ThemeId, ThemeConfig> = {
	kinari,
	sakura,
	sumi,
};
