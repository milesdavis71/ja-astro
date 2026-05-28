import { readdir } from 'node:fs/promises'
import path from 'node:path'

export type GalleryImage = {
  src: string
  thumb?: string
  cover?: string
  alt?: string
}

export type GalleryPreviewResponse = {
  folder: string
  offset: number
  limit: number
  total: number
  images: GalleryImage[]
}

const ALLOWED_EXTENSIONS = new Set(['.jpg', '.jpeg', '.png', '.webp', '.avif'])

export function isGalleryFolder(folder: string) {
  return folder.startsWith('/images/galeriak/')
}

function toPublicPath(folder: string) {
  return folder.replace(/^\/+/, '').replaceAll('/', path.sep)
}

function isAllowedImage(file: string) {
  return ALLOWED_EXTENSIONS.has(path.extname(file).toLowerCase())
}

function buildAlt(index: number) {
  return `Galéria kép ${index + 1}`
}

function findVariantName(file: string, availableFiles: Set<string>) {
  const extension = path.extname(file)
  const name = path.basename(file, extension)
  const candidates = [
    file,
    `${name}_thumb${extension}`,
    `${name}-thumb${extension}`,
  ]

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

export async function getGalleryImages(folder: string): Promise<GalleryImage[]> {
  if (!isGalleryFolder(folder)) return []

  const galleryRoot = path.join(process.cwd(), 'public', toPublicPath(folder))
  const fullPath = path.join(galleryRoot, 'full')
  const thumbPath = path.join(galleryRoot, 'thumb')

  let fullFiles: string[] = []
  let thumbFiles: string[] = []
  let rootFiles: string[] = []

  try {
    ;[fullFiles, thumbFiles, rootFiles] = await Promise.all([readdir(fullPath), readdir(thumbPath), readdir(galleryRoot)])
  } catch {
    return []
  }

  const thumbSet = new Set(thumbFiles.filter(isAllowedImage))
  const coverFile = findCoverName(rootFiles)
  const sortedFiles = fullFiles.filter(isAllowedImage).sort((a, b) => a.localeCompare(b, 'hu', { numeric: true }))

  return sortedFiles.map((file, index) => {
    const thumbFile = findVariantName(file, thumbSet)

    return {
      src: `${folder}/full/${file}`,
      thumb: thumbFile ? `${folder}/thumb/${thumbFile}` : undefined,
      cover: index === 0 && coverFile ? `${folder}/${coverFile}` : undefined,
      alt: buildAlt(index),
    }
  })
}

export async function getGalleryPreview(
  folder: string,
  options?: {
    offset?: number
    limit?: number
  }
): Promise<GalleryPreviewResponse> {
  const offset = Math.max(0, options?.offset ?? 0)
  const limit = options?.limit ?? 9
  const allImages = await getGalleryImages(folder)
  const images = limit <= 0 ? allImages.slice(offset) : allImages.slice(offset, offset + limit)

  return {
    folder,
    offset,
    limit,
    total: allImages.length,
    images,
  }
}
