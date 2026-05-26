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
    label: 'Tanitsunk Magyarorszagert',
    description: 'Taborok, palyaorientacios napok, nyilt napok es kozossegi programok.',
    categoryHref: '#tanitsunk-magyarorszagert',
  },
  'szte-juniorok-viadala-sakk-viadal': {
    label: 'SZTE Juniorok Viadala',
    description: 'Dontok, elodontok, sakkviadal es kapcsolodo versenyek.',
    categoryHref: '#szte-juniorok-viadala',
  },
  'ja-eloadasok': {
    label: 'Junior Akademia eloadasok',
    description: 'Tudomanyos es ismeretterjeszto eloadasok kulonfele temakban.',
    categoryHref: '#junior-akademia-eloadasok',
  },
  'egyeb-programok': {
    label: 'Egyeb programok',
    description: 'Dijatadok, workshopok, prezilimpia es kulonleges rendezvenyek.',
    categoryHref: '#egyeb-programok',
  },
  kpp: {
    label: 'Kozepiskolai Partneri Program',
    description: 'Igazgatoi ertekezletek, egyuttmukodesek es partnerintezmenyi esemenyek.',
    categoryHref: '#kozepiskolai-partneri-program',
  },
  osztalykirandulasok: {
    label: 'Osztalykirandulasok',
    description: 'Iskolai csoportok latogatasai ev es intezmeny szerint.',
    categoryHref: '#osztalykirandulasok',
  },
  'prezentacios-technikak-workshopok-2020': {
    label: 'Workshopok es treningek',
    description: 'Prezentacios technikak, keszsegfejlesztes es egyeb tematikus alkalmak.',
    categoryHref: '#workshopok-es-treningek',
  },
  'talent-dijatadok': {
    label: 'Osztondijak es dijatadok',
    description: 'Talent es mas elismeresek unnepi pillanatai.',
    categoryHref: '#osztondijak-es-dijatadok',
  },
}

const galleryRoot = path.join(process.cwd(), 'public', 'images', 'galeriak')

function titleCaseWord(word: string) {
  if (!word) return word
  if (/^\d+$/.test(word)) return word
  return word.charAt(0).toUpperCase() + word.slice(1)
}

export function humanizeGallerySlug(value: string) {
  return value
    .replace(/^_+/, '')
    .replaceAll('_', ' ')
    .replaceAll('-', ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .split(' ')
    .map(titleCaseWord)
    .join(' ')
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
    if (slug.includes('tabor')) return 'TM taborok'
    if (slug.includes('palyaorientacios')) return 'Palyaorientacios napok'
    if (slug.includes('prevencios')) return 'Prevencios napok'
    if (slug.includes('nyilt-nap')) return 'Nyilt napok'
    return 'Kulon esemenyek'
  }

  if (rootKey === 'szte-juniorok-viadala-sakk-viadal') {
    if (slug.includes('donto')) return 'Dontok'
    if (slug.includes('elodonto')) return 'Elodontok'
    if (slug.includes('sakk')) return 'Sakkviadal'
    if (slug.includes('plakat')) return 'Plakatverseny'
    return 'Kapcsolodo esemenyek'
  }

  if (rootKey === 'egyeb-programok') {
    if (segments[1] === 'osztondij-atadok') return 'Osztondijak es dijatadok'
    if (segments[1] === 'prezilimpia-donto') return 'Prezilimpia'
    if (slug.includes('kommunikacios')) return 'Keszsegfejleszto treningek'
    return 'Egyeb programok'
  }

  if (rootKey === 'kpp') {
    if (slug.includes('igazgatoi')) return 'Igazgatoi ertekezletek'
    if (slug.includes('egyuttmukodesi')) return 'Egyuttmukodesek'
    if (slug.includes('prospektustarto')) return 'Prospektustarto-atadasok'
    return 'KPP esemenyek'
  }

  if (rootKey === 'osztalykirandulasok') {
    return segments[1] ? `${segments[1]}. evi osztalykirandulasok` : 'Osztalykirandulasok'
  }

  if (rootKey === 'prezentacios-technikak-workshopok-2020') {
    return 'Prezentacios technikak'
  }

  if (rootKey === 'talent-dijatadok') {
    return 'Talent dijatadok'
  }

  if (rootKey === 'ja-eloadasok') {
    return 'Junior Akademia eloadasok'
  }

  return GALLERY_ROOT_CONFIG[rootKey]?.label || 'Galeriak'
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
