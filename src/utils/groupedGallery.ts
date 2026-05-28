import { readdir } from 'node:fs/promises'
import path from 'node:path'
import { isGalleryFolder, type GalleryImage } from './gallery'

export type GroupedGallerySection = {
  slug: string
  title: string
  images: GalleryImage[]
}

const ALLOWED_EXTENSIONS = new Set(['.jpg', '.jpeg', '.png', '.webp', '.avif'])

export const GROUPED_GALLERY_FOLDERS = new Set([
  '/images/galeriak/szte-juniorok-viadala-sakk-viadal/szte-juniorok-viadala-elodonto-2023',
  '/images/galeriak/szte-juniorok-viadala-sakk-viadal/szte-juniorok-viadala-elodonto-2024',
])

function toPublicPath(folder: string) {
  return folder.replace(/^\/+/, '').replaceAll('/', path.sep)
}

function isAllowedImage(file: string) {
  return ALLOWED_EXTENSIONS.has(path.extname(file).toLowerCase())
}

async function readDirectory(directory: string) {
  try {
    return await readdir(directory)
  } catch {
    return []
  }
}

async function readDirectoryEntries(directory: string) {
  try {
    return await readdir(directory, { withFileTypes: true })
  } catch {
    return []
  }
}

function findVariantName(file: string, availableFiles: Set<string>) {
  const extension = path.extname(file)
  const name = path.basename(file, extension)
  const candidates = [file, `${name}_thumb${extension}`, `${name}-thumb${extension}`]

  if (name.endsWith('_full')) {
    const baseName = name.slice(0, -5)
    candidates.push(`${baseName}_thumb${extension}`, `${baseName}${extension}`)
  }

  if (name.endsWith('-full')) {
    const baseName = name.slice(0, -5)
    candidates.push(`${baseName}-thumb${extension}`, `${baseName}${extension}`)
  }

  return candidates.find((candidate) => availableFiles.has(candidate))
}

function findCoverName(files: string[]) {
  return files
    .filter(isAllowedImage)
    .find((file) => path.basename(file, path.extname(file)).toLowerCase().endsWith('_cover'))
}

function titleCaseWord(word: string) {
  if (!word) return word
  if (/^\d+$/.test(word)) return word
  return word.charAt(0).toUpperCase() + word.slice(1)
}

function humanizeSchoolSlug(value: string) {
  return value.replaceAll('-', ' ').replace(/\s+/g, ' ').trim().split(' ').map(titleCaseWord).join(' ')
}

export function isGroupedGalleryFolder(folder: string) {
  return GROUPED_GALLERY_FOLDERS.has(folder)
}

export async function getGroupedGalleryCover(folder: string) {
  if (!isGalleryFolder(folder)) return ''

  const galleryRoot = path.join(process.cwd(), 'public', toPublicPath(folder))
  const rootFiles = await readDirectory(galleryRoot)
  const coverName = findCoverName(rootFiles)

  return coverName ? `${folder}/${coverName}` : ''
}

export async function getGroupedGallerySections(
  folder: string,
  schoolNames: Record<string, string> = {}
): Promise<GroupedGallerySection[]> {
  if (!isGroupedGalleryFolder(folder)) return []

  const galleryRoot = path.join(process.cwd(), 'public', toPublicPath(folder))
  const entries = await readDirectoryEntries(galleryRoot)
  const schoolFolders = entries
    .filter((entry) => entry.isDirectory())
    .map((entry) => entry.name)
    .sort((left, right) => {
      const leftTitle = schoolNames[left] || humanizeSchoolSlug(left)
      const rightTitle = schoolNames[right] || humanizeSchoolSlug(right)
      return leftTitle.localeCompare(rightTitle, 'hu')
    })

  const sections = await Promise.all(
    schoolFolders.map(async (schoolSlug) => {
      const schoolRoot = path.join(galleryRoot, schoolSlug)
      const fullPath = path.join(schoolRoot, 'full')
      const thumbPath = path.join(schoolRoot, 'thumb')
      const [fullFiles, thumbFiles] = await Promise.all([readDirectory(fullPath), readDirectory(thumbPath)])
      const thumbSet = new Set(thumbFiles.filter(isAllowedImage))
      const sortedFiles = fullFiles.filter(isAllowedImage).sort((a, b) => a.localeCompare(b, 'hu', { numeric: true }))
      const title = schoolNames[schoolSlug] || humanizeSchoolSlug(schoolSlug)

      return {
        slug: schoolSlug,
        title,
        images: sortedFiles.map((file, index) => {
          const thumbFile = findVariantName(file, thumbSet)

          return {
            src: `${folder}/${schoolSlug}/full/${file}`,
            thumb: thumbFile ? `${folder}/${schoolSlug}/thumb/${thumbFile}` : undefined,
            alt: `${title} - ${index + 1}. kép`,
          }
        }),
      }
    })
  )

  return sections.filter((section) => section.images.length > 0)
}

export async function getGroupedGalleryImageCount(folder: string) {
  const sections = await getGroupedGallerySections(folder)
  return sections.reduce((total, section) => total + section.images.length, 0)
}
