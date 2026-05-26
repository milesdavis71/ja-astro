import { readdir } from 'node:fs/promises'
import path from 'node:path'
import { getGalleryImages } from './gallery'

export type GalleryCatalogItem = {
  folder: string
  relativePath: string
  routePath: string
  slug: string
  title: string
  rootKey: string
  rootLabel: string
  rootDescription: string
  rootAnchor: string
  sectionLabel: string
  year: number | null
  seasonWeight: number
  imageCount: number
  coverImage: string
}

export const GALLERY_ROOT_CONFIG: Record<
  string,
  {
    label: string
    description: string
    categoryHref: string
  }
> = {
  'tanitsunk-mo-ert': {
    label: 'Tanítsunk Magyarországért',
    description: 'Táborok, pályaorientációs napok, nyílt napok és közösségi programok.',
    categoryHref: '#tanitsunk-magyarorszagert',
  },
  'szte-juniorok-viadala-sakk-viadal': {
    label: 'SZTE Juniorok Viadala',
    description: 'Döntők, elődöntők, sakkviadal és kapcsolódó versenyek.',
    categoryHref: '#szte-juniorok-viadala',
  },
  'ja-eloadasok': {
    label: 'Junior Akadémia előadások',
    description: 'Tudományos és ismeretterjesztő előadások különféle témákban.',
    categoryHref: '#junior-akademia-eloadasok',
  },
  'egyeb-programok': {
    label: 'Egyéb programok',
    description: 'Díjátadók, workshopok, prezilimpia és különleges rendezvények.',
    categoryHref: '#egyeb-programok',
  },
  kpp: {
    label: 'Középiskolai Partneri Program',
    description: 'Igazgatói értekezletek, együttműködések és partnerintézményi események.',
    categoryHref: '#kozepiskolai-partneri-program',
  },
  osztalykirandulasok: {
    label: 'Osztálykirándulások',
    description: 'Iskolai csoportok látogatásai év és intézmény szerint.',
    categoryHref: '#osztalykirandulasok',
  },
  'prezentacios-technikak-workshopok-2020': {
    label: 'Workshopok és tréningek',
    description: 'Prezentációs technikák, készségfejlesztés és egyéb tematikus alkalmak.',
    categoryHref: '#workshopok-es-treningek',
  },
  'talent-dijatadok': {
    label: 'Ösztöndíjak és díjátadók',
    description: 'Talent és más elismerések ünnepi pillanatai.',
    categoryHref: '#osztondijak-es-dijatadok',
  },
}

const galleryRoot = path.join(process.cwd(), 'public', 'images', 'galeriak')

function titleCaseWord(word: string) {
  if (!word) return word
  if (/^\d+$/.test(word)) return word
  return word.charAt(0).toUpperCase() + word.slice(1)
}

function applyHungarianTitleCorrections(value: string) {
  const corrections: Record<string, string> = {
    Akademia: 'Akadémia',
    Eloadasok: 'Előadások',
    Eloadas: 'Előadás',
    Donto: 'Döntő',
    Dontok: 'Döntők',
    Elodonto: 'Elődöntő',
    Elodontok: 'Elődöntők',
    Tabor: 'Tábor',
    Taborok: 'Táborok',
    Nyilt: 'Nyílt',
    Fotoalbum: 'Fotóalbum',
    Foto: 'Fotó',
    Galeria: 'Galéria',
    Kep: 'Kép',
    Kepekben: 'Képekben',
    Kapcsolodo: 'Kapcsolódó',
    Kulon: 'Külön',
    Kozossegi: 'Közösségi',
    Palyaorientacios: 'Pályaorientációs',
    Prevencios: 'Prevenciós',
    Egyeb: 'Egyéb',
    Dijatadok: 'Díjátadók',
    Osztondijak: 'Ösztöndíjak',
    Osztondij: 'Ösztöndíj',
    Tanitsunk: 'Tanítsunk',
    Magyarorszagert: 'Magyarországért',
    Kozepiskolai: 'Középiskolai',
    Osztalykirandulasok: 'Osztálykirándulások',
    Prezentacios: 'Prezentációs',
    Keszsegfejleszto: 'Készségfejlesztő',
    Keszsegfejlesztes: 'Készségfejlesztés',
    Treningek: 'Tréningek',
    Tudomanyos: 'Tudományos',
    Ismeretterjeszto: 'Ismeretterjesztő',
    Kulonfele: 'Különféle',
    Temakban: 'Témákban',
    Igazgatoi: 'Igazgatói',
    Ertekezletek: 'Értekezletek',
    Egyuttmukodesek: 'Együttműködések',
    Partnerintezmenyi: 'Partnerintézményi',
    Esemenyek: 'Események',
    Ev: 'Év',
    Evi: 'Évi',
    Intezmeny: 'Intézmény',
    Latogatasai: 'Látogatásai',
    Unnepi: 'Ünnepi',
    Mas: 'Más',
  }

  return value
    .split(' ')
    .map((word) => corrections[word] || word)
    .join(' ')
}

export function humanizeGallerySlug(value: string) {
  return applyHungarianTitleCorrections(
    value
    .replace(/^_+/, '')
    .replaceAll('_', ' ')
    .replaceAll('-', ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .split(' ')
    .map(titleCaseWord)
    .join(' ')
  )
}

function extractYear(value: string) {
  const match = value.match(/(20\d{2})/)
  return match ? Number(match[1]) : null
}

function extractSeasonWeight(value: string) {
  if (value.includes('osz')) return 30
  if (value.includes('tavasz')) return 20
  if (value.includes('nyar')) return 10
  return 0
}

function getSectionLabel(rootKey: string, segments: string[], slug: string) {
  if (rootKey === 'tanitsunk-mo-ert') {
    if (slug.includes('tabor')) return 'TM táborok'
    if (slug.includes('palyaorientacios')) return 'Pályaorientációs napok'
    if (slug.includes('prevencios')) return 'Prevenciós napok'
    if (slug.includes('nyilt-nap')) return 'Nyílt napok'
    return 'Külön események'
  }

  if (rootKey === 'szte-juniorok-viadala-sakk-viadal') {
    if (slug.includes('donto')) return 'Döntők'
    if (slug.includes('elodonto')) return 'Elődöntők'
    if (slug.includes('sakk')) return 'Sakkviadal'
    if (slug.includes('plakat')) return 'Plakátverseny'
    return 'Kapcsolódó események'
  }

  if (rootKey === 'egyeb-programok') {
    if (segments[1] === 'osztondij-atadok') return 'Ösztöndíjak és díjátadók'
    if (segments[1] === 'prezilimpia-donto') return 'Prezilimpia'
    if (slug.includes('kommunikacios')) return 'Készségfejlesztő tréningek'
    return 'Egyéb programok'
  }

  if (rootKey === 'kpp') {
    if (slug.includes('igazgatoi')) return 'Igazgatói értekezletek'
    if (slug.includes('egyuttmukodesi')) return 'Együttműködések'
    if (slug.includes('prospektustarto')) return 'Prospektustartó-átadások'
    return 'KPP események'
  }

  if (rootKey === 'osztalykirandulasok') {
    return segments[1] ? `${segments[1]}. évi osztálykirándulások` : 'Osztálykirándulások'
  }

  if (rootKey === 'prezentacios-technikak-workshopok-2020') {
    return 'Prezentációs technikák'
  }

  if (rootKey === 'talent-dijatadok') {
    return 'Talent díjátadók'
  }

  if (rootKey === 'ja-eloadasok') {
    return 'Junior Akadémia előadások'
  }

  return GALLERY_ROOT_CONFIG[rootKey]?.label || 'Galériák'
}

async function collectGalleryFolders(currentDir: string, relativeSegments: string[] = []): Promise<string[]> {
  const entries = await readdir(currentDir, { withFileTypes: true })
  const hasFull = entries.some((entry) => entry.isDirectory() && entry.name === 'full')
  const hasThumb = entries.some((entry) => entry.isDirectory() && entry.name === 'thumb')

  if (hasFull && hasThumb) {
    return ['/' + path.posix.join('images', 'galeriak', ...relativeSegments)]
  }

  const nestedResults = await Promise.all(
    entries
      .filter((entry) => entry.isDirectory())
      .map((entry) => collectGalleryFolders(path.join(currentDir, entry.name), [...relativeSegments, entry.name]))
  )

  return nestedResults.flat()
}

export async function getGalleryCatalogItems(): Promise<GalleryCatalogItem[]> {
  const allFolders = await collectGalleryFolders(galleryRoot)
  const allItems: GalleryCatalogItem[] = []

  for (const folder of allFolders) {
    const images = await getGalleryImages(folder)
    if (images.length === 0) continue

    const relativePath = folder.replace('/images/galeriak/', '')
    const segments = relativePath.split('/')
    const rootKey = segments[0]
    const rootConfig = GALLERY_ROOT_CONFIG[rootKey]
    if (!rootConfig) continue

    const slug = segments[segments.length - 1]

    allItems.push({
      folder,
      relativePath,
      routePath: `/galeriak/${relativePath}`,
      slug,
      title: humanizeGallerySlug(slug),
      rootKey,
      rootLabel: rootConfig.label,
      rootDescription: rootConfig.description,
      rootAnchor: rootConfig.categoryHref,
      sectionLabel: getSectionLabel(rootKey, segments, slug),
      year: extractYear(relativePath),
      seasonWeight: extractSeasonWeight(relativePath),
      imageCount: images.length,
      coverImage: images[0].thumb || images[0].src,
    })
  }

  allItems.sort((left, right) => {
    const yearDiff = (right.year || 0) - (left.year || 0)
    if (yearDiff !== 0) return yearDiff
    const seasonDiff = right.seasonWeight - left.seasonWeight
    if (seasonDiff !== 0) return seasonDiff
    return left.title.localeCompare(right.title, 'hu')
  })

  return allItems
}

export function getGalleryCategoryBackLink(item: GalleryCatalogItem) {
  return `/galeriak/fotoalbumok${item.rootAnchor}`
}
