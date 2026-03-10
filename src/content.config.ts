import { defineCollection, z } from 'astro:content'
import { file } from 'astro/loaders'

const news = defineCollection({
  type: 'content',
  schema: z.object({
    id: z.number(),
    title: z.string(),
    fulltitle: z.string(),
    date: z.date(),
    excerpt: z.string(),
    lead: z.string(),
    image: z.string(),
    draft: z.boolean().optional(),
    galleryUrl: z.string().optional(),
    galleryFolder: z.string().optional(),
    galleryImages: z
      .array(
        z.object({
          src: z.string(),
          thumb: z.string().optional(),
          alt: z.string().optional(),
        })
      )
      .optional(),
    // category: z.string().optional(),
  }),
})

const global = defineCollection({
  type: 'data',
  schema: z.object({
    menu: z.array(
      z.object({
        id: z.string(),
        label: z.string(),
        url: z.string(),
      })
    ),
    site: z.object({
      title: z.string(),
      description: z.string().optional(),
    }),
    footer: z.object({
      logo: z.string(),
      buttons: z.array(
        z.object({
          text: z.string(),
          url: z.string().nullable().optional(),
        })
      ),
      contact: z.object({
        title: z.string(),
        items: z.array(
          z.object({
            text: z.string(),
            linktext: z.string(),
            url: z.string(),
          })
        ),
      }),
      links: z.object({
        title: z.string(),
        items: z.array(
          z.object({
            text: z.string(),
            url: z.string(),
          })
        ),
      }),
      address: z.string().optional(),
      email: z.string().optional(),
    }),
  }),
})

const pressMentions = defineCollection({
  loader: file('src/data/sajtomegjelenesek.yml'),
  schema: z.array(
    z.object({
      id: z.string(),
      date: z.string(),
      title: z.string(),
      url: z.string().url(),
      source: z.string(),
      screenshot: z.string(),
    })
  ),
})

export const collections = {
  news,
  global,
  pressMentions,
}
