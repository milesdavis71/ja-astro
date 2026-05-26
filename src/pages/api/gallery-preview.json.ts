import { getGalleryPreview, isGalleryFolder } from '../../utils/gallery'

export async function GET({ url }: { url: URL }) {
  const folder = (url.searchParams.get('folder') || '').trim()
  if (!folder) {
    return Response.json({ error: 'Missing folder parameter.' }, { status: 400 })
  }

  if (!isGalleryFolder(folder)) {
    return Response.json({ error: 'Invalid folder path.' }, { status: 400 })
  }

  const offsetParam = Number(url.searchParams.get('offset') || '0')
  const limitParam = Number(url.searchParams.get('limit') || '9')

  const payload = await getGalleryPreview(folder, {
    offset: Number.isFinite(offsetParam) ? offsetParam : 0,
    limit: Number.isFinite(limitParam) ? limitParam : 9,
  })

  if (payload.total === 0) {
    return Response.json({ error: 'Gallery folder not found.' }, { status: 404 })
  }

  return Response.json(payload)
}
