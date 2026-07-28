/**
 * Resolve menu/route display title with i18n.
 * Prefer meta.i18n, then menu.{name}, then meta.title.
 */
export function resolveMenuTitle(
  item: { name?: unknown, meta?: { i18n?: unknown, title?: unknown } } | null | undefined,
  translate: (key: string) => string,
): string {
  const title = typeof item?.meta?.title === 'string' ? item.meta.title : ''
  const explicit = typeof item?.meta?.i18n === 'string' ? item.meta.i18n.trim() : ''
  const name = typeof item?.name === 'string' ? item.name.trim() : ''
  const candidates = [explicit, name ? `menu.${name}` : ''].filter(Boolean) as string[]

  for (const key of candidates) {
    const result = translate(key)
    if (result && result !== key) {
      return result
    }
  }

  return title
}
