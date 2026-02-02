// @ts-check
import { defineConfig } from 'astro/config'

import tailwindcss from '@tailwindcss/vite'
import yaml from '@rollup/plugin-yaml'
import mdx from '@astrojs/mdx'
import icon from 'astro-icon'

// https://astro.build/config
export default defineConfig({
  integrations: [mdx(), icon()],
  vite: {
    plugins: [tailwindcss(), yaml()],
  },
})
