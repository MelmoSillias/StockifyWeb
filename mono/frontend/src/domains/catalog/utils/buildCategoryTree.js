/**
 * Build a PrimeVue TreeTable node list from a flat category list (parent_id).
 * @param {Array<{ id: string, parent_id?: string|null, name?: string, sort_order?: number }>} items
 * @returns {import('primevue/treenode').TreeNode[]}
 */
export const buildCategoryTree = (items) => {
  const list = Array.isArray(items) ? items : []
  const byId = new Map()

  for (const item of list) {
    byId.set(item.id, {
      key: String(item.id),
      label: item.name,
      data: item,
      children: []
    })
  }

  const roots = []

  for (const item of list) {
    const node = byId.get(item.id)
    const parentId = item.parent_id
    if (parentId && byId.has(parentId)) {
      byId.get(parentId).children.push(node)
    } else {
      roots.push(node)
    }
  }

  const sortNodes = (nodes) => {
    nodes.sort((a, b) => {
      const orderA = a.data?.sort_order ?? 0
      const orderB = b.data?.sort_order ?? 0
      if (orderA !== orderB) return orderA - orderB
      return String(a.label || '').localeCompare(String(b.label || ''), 'fr')
    })
    for (const node of nodes) {
      if (node.children?.length) {
        sortNodes(node.children)
      } else {
        node.children = undefined
      }
    }
  }

  sortNodes(roots)
  return roots
}

/**
 * Filter categories keeping matches and their ancestors, then rebuild the tree.
 * @param {Array} items
 * @param {string} query
 * @param {(item: object) => string[]} getSearchValues
 */
export const filterCategoryTree = (items, query, getSearchValues = (item) => [item.name]) => {
  const normalized = String(query || '')
    .trim()
    .toLowerCase()

  if (!normalized) {
    return buildCategoryTree(items)
  }

  const byId = new Map(items.map((item) => [item.id, item]))
  const keep = new Set()

  for (const item of items) {
    const haystack = getSearchValues(item)
      .map((value) => String(value || '').toLowerCase())
      .join(' ')

    if (!haystack.includes(normalized)) {
      continue
    }

    keep.add(item.id)
    let parentId = item.parent_id
    while (parentId && byId.has(parentId)) {
      keep.add(parentId)
      parentId = byId.get(parentId).parent_id
    }
  }

  return buildCategoryTree(items.filter((item) => keep.has(item.id)))
}
