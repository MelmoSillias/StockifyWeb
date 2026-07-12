/**
 * Generates a realistic MySQL seed for Stockify mono catalog/inventory.
 * Period: 2026-04-11 → 2026-07-11 (today).
 *
 * Usage: node scripts/generate-realistic-seed.mjs
 * Output: seeds/realistic_catalog_seed.sql
 */
import { mkdirSync, writeFileSync } from 'node:fs'
import { dirname, join } from 'node:path'
import { fileURLToPath } from 'node:url'

const __dirname = dirname(fileURLToPath(import.meta.url))
const root = join(__dirname, '..')
const outPath = join(root, 'seeds', 'realistic_catalog_seed.sql')

const TODAY = new Date('2026-07-11T18:00:00')
const PERIOD_START = new Date('2026-04-11T08:00:00')

let seq = 1
const makeUuid = () => {
  const n = seq++
  const h = n.toString(16).padStart(12, '0')
  return `b0000000-0000-7000-8000-${h}`
}

const bin = (id) => `UNHEX(REPLACE('${id}', '-', ''))`
const esc = (s) => String(s).replace(/\\/g, '\\\\').replace(/'/g, "''")
const sqlStr = (s) => (s == null ? 'NULL' : `'${esc(s)}'`)
const sqlDec = (n, scale = 3) => Number(n).toFixed(scale)

const addDays = (date, days) => {
  const d = new Date(date)
  d.setDate(d.getDate() + days)
  return d
}

const addHours = (date, hours) => {
  const d = new Date(date)
  d.setHours(d.getHours() + hours)
  return d
}

// Seeded PRNG for reproducibility
let seed = 20260711
const rnd = () => {
  seed = (seed * 1664525 + 1013904223) >>> 0
  return seed / 0xffffffff
}
const rndInt = (min, max) => min + Math.floor(rnd() * (max - min + 1))
const rndPick = (arr) => arr[rndInt(0, arr.length - 1)]
const rndFloat = (min, max, scale = 2) => {
  const v = min + rnd() * (max - min)
  return Number(v.toFixed(scale))
}

const fmtDt = (d) => {
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())} ${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`
}
const fmtDate = (d) => {
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`
}

const dayOffset = (from, to) => Math.floor((to - from) / 86400000)

const randomInPeriod = () => {
  const span = TODAY - PERIOD_START
  return new Date(PERIOD_START.getTime() + Math.floor(rnd() * span))
}

const UNIT_IDS = {
  piece: makeUuid(),
  kg: makeUuid(),
  liter: makeUuid(),
  carton: makeUuid()
}

const UNITS = [
  { id: UNIT_IDS.piece, code: 'piece', label: 'Pièce', decimals: 0 },
  { id: UNIT_IDS.kg, code: 'kg', label: 'Kilogramme', decimals: 3 },
  { id: UNIT_IDS.liter, code: 'liter', label: 'Litre', decimals: 3 },
  { id: UNIT_IDS.carton, code: 'carton', label: 'Carton', decimals: 0 }
]

const CATALOG = [
  {
    name: 'Boissons',
    products: [
      { name: 'Eau minérale Cristal', ref: 'EAU-CRI', desc: 'Eau minérale naturelle Cristal', variants: [
        { unit: 'piece', mode: 'unit', pack: '1L', price: 350, cost: 180, alert: 30 },
        { unit: 'liter', mode: 'volume', pack: '5L', price: 1200, cost: 700, alert: 15 },
        { unit: 'carton', mode: 'bundle', pack: 'Carton12x1L', price: 3800, cost: 2200, alert: 8 }
      ]},
      { name: 'Eau minérale Supermont', ref: 'EAU-SUP', desc: 'Eau minérale Supermont', variants: [
        { unit: 'piece', mode: 'unit', pack: '1.5L', price: 400, cost: 200, alert: 25 },
        { unit: 'carton', mode: 'bundle', pack: 'Carton6x1.5L', price: 2200, cost: 1300, alert: 6 }
      ]},
      { name: 'Coca-Cola', ref: 'COCA', desc: 'Boisson gazeuse Coca-Cola', variants: [
        { unit: 'piece', mode: 'unit', pack: '33cl', price: 300, cost: 160, alert: 40 },
        { unit: 'liter', mode: 'volume', pack: '1L', price: 700, cost: 400, alert: 20 },
        { unit: 'carton', mode: 'bundle', pack: 'Pack6x33cl', price: 1600, cost: 950, alert: 10 }
      ]},
      { name: 'Fanta Orange', ref: 'FANTA', desc: 'Boisson gazeuse orange', variants: [
        { unit: 'piece', mode: 'unit', pack: '1L', price: 650, cost: 380, alert: 20 },
        { unit: 'carton', mode: 'bundle', pack: 'Pack6x1L', price: 3600, cost: 2100, alert: 8 }
      ]},
      { name: 'Jus Tangui', ref: 'JUS-TAN', desc: 'Jus de fruits Tangui', variants: [
        { unit: 'piece', mode: 'unit', pack: '25cl', price: 250, cost: 120, alert: 35 },
        { unit: 'liter', mode: 'volume', pack: '1L', price: 800, cost: 450, alert: 18 }
      ]},
      { name: 'Bière Castel', ref: 'CASTEL', desc: 'Bière Castel Beer', variants: [
        { unit: 'piece', mode: 'unit', pack: '65cl', price: 700, cost: 420, alert: 24 },
        { unit: 'carton', mode: 'bundle', pack: 'Casier12', price: 7800, cost: 4800, alert: 5 }
      ]},
      { name: 'Boisson énergétique XXL', ref: 'NRG-XXL', desc: 'Boisson énergisante', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Canette', price: 500, cost: 280, alert: 30 }
      ]},
      { name: 'Thé glacé Lipton', ref: 'LIPTON', desc: 'Thé glacé prêt à boire', variants: [
        { unit: 'piece', mode: 'unit', pack: '50cl', price: 450, cost: 250, alert: 22 },
        { unit: 'carton', mode: 'bundle', pack: 'Pack12', price: 4800, cost: 2800, alert: 6 }
      ]},
      { name: 'Lait Nido liquide', ref: 'LAIT-NIDO', desc: 'Lait UHT', variants: [
        { unit: 'liter', mode: 'volume', pack: '1L', price: 900, cost: 550, alert: 20 },
        { unit: 'carton', mode: 'bundle', pack: 'Pack6x1L', price: 5100, cost: 3100, alert: 7 }
      ]},
      { name: 'Eau gazeuse Top', ref: 'EAU-TOP', desc: 'Eau gazeuse aromatisée', variants: [
        { unit: 'piece', mode: 'unit', pack: '1L', price: 550, cost: 300, alert: 15 }
      ]},
      { name: 'Malta Guinness', ref: 'MALTA', desc: 'Boisson maltée non alcoolisée', variants: [
        { unit: 'piece', mode: 'unit', pack: '33cl', price: 400, cost: 220, alert: 28 },
        { unit: 'carton', mode: 'bundle', pack: 'Pack24', price: 8500, cost: 5200, alert: 4 }
      ]},
      { name: 'Jus Boost', ref: 'BOOST', desc: 'Jus vitaminé Boost', variants: [
        { unit: 'liter', mode: 'volume', pack: '1L', price: 750, cost: 420, alert: 16 }
      ]}
    ]
  },
  {
    name: 'Épicerie',
    products: [
      { name: 'Riz parfumé Dynasty', ref: 'RIZ-DYN', desc: 'Riz long grain parfumé', variants: [
        { unit: 'kg', mode: 'weight', pack: '1kg', price: 1200, cost: 800, alert: 25 },
        { unit: 'piece', mode: 'unit', pack: '5kg', price: 5500, cost: 3800, alert: 10 },
        { unit: 'carton', mode: 'bundle', pack: '25kg', price: 25000, cost: 18000, alert: 4 }
      ]},
      { name: 'Huile Mayor', ref: 'HUILE-MAY', desc: 'Huile végétale Mayor', variants: [
        { unit: 'liter', mode: 'volume', pack: '1L', price: 1500, cost: 1050, alert: 20 },
        { unit: 'piece', mode: 'unit', pack: '5L', price: 7000, cost: 5000, alert: 8 }
      ]},
      { name: 'Sucre en poudre', ref: 'SUCRE', desc: 'Sucre cristallisé', variants: [
        { unit: 'kg', mode: 'weight', pack: '1kg', price: 900, cost: 650, alert: 30 },
        { unit: 'carton', mode: 'bundle', pack: '50kg', price: 40000, cost: 30000, alert: 3 }
      ]},
      { name: 'Farine de blé', ref: 'FARINE', desc: 'Farine de blé panifiable', variants: [
        { unit: 'kg', mode: 'weight', pack: '1kg', price: 800, cost: 550, alert: 25 },
        { unit: 'carton', mode: 'bundle', pack: '25kg', price: 18000, cost: 13000, alert: 5 }
      ]},
      { name: 'Spaghetti Panzani', ref: 'SPAG', desc: 'Pâtes spaghetti', variants: [
        { unit: 'piece', mode: 'unit', pack: '500g', price: 700, cost: 420, alert: 40 },
        { unit: 'carton', mode: 'bundle', pack: 'Carton20', price: 13000, cost: 8000, alert: 6 }
      ]},
      { name: 'Sardines en conserve', ref: 'SARD', desc: 'Sardines à l\'huile', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Boîte', price: 450, cost: 280, alert: 50 },
        { unit: 'carton', mode: 'bundle', pack: 'Carton48', price: 19000, cost: 12000, alert: 5 }
      ]},
      { name: 'Tomate concentrée', ref: 'TOMATE', desc: 'Double concentré de tomate', variants: [
        { unit: 'piece', mode: 'unit', pack: '70g', price: 150, cost: 80, alert: 60 },
        { unit: 'carton', mode: 'bundle', pack: '400g', price: 600, cost: 350, alert: 25 }
      ]},
      { name: 'Cube Maggi', ref: 'MAGGI', desc: 'Assaisonnement Maggi', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Tablette', price: 50, cost: 25, alert: 100 },
        { unit: 'carton', mode: 'bundle', pack: 'Boîte60', price: 2800, cost: 1600, alert: 12 }
      ]},
      { name: 'Lait en poudre Nido', ref: 'NIDO-PDR', desc: 'Lait en poudre', variants: [
        { unit: 'piece', mode: 'unit', pack: '400g', price: 3500, cost: 2500, alert: 15 },
        { unit: 'kg', mode: 'weight', pack: '2.5kg', price: 18000, cost: 13000, alert: 6 }
      ]},
      { name: 'Haricots rouges', ref: 'HARICOT', desc: 'Haricots secs', variants: [
        { unit: 'kg', mode: 'weight', pack: '1kg', price: 1100, cost: 750, alert: 20 }
      ]},
      { name: 'Maïs en conserve', ref: 'MAIS', desc: 'Maïs doux en conserve', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Boîte', price: 650, cost: 380, alert: 30 }
      ]},
      { name: 'Vinaigre blanc', ref: 'VINAIGRE', desc: 'Vinaigre d\'alcool', variants: [
        { unit: 'liter', mode: 'volume', pack: '1L', price: 500, cost: 280, alert: 18 }
      ]},
      { name: 'Sel de cuisine', ref: 'SEL', desc: 'Sel fin iodé', variants: [
        { unit: 'kg', mode: 'weight', pack: '1kg', price: 300, cost: 150, alert: 40 }
      ]},
      { name: 'Poivre noir moulu', ref: 'POIVRE', desc: 'Poivre noir', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Sachet50g', price: 400, cost: 220, alert: 25 }
      ]}
    ]
  },
  {
    name: 'Hygiène & Beauté',
    products: [
      { name: 'Savon de Marseille', ref: 'SAVON-MAR', desc: 'Savon toilette', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Unité', price: 250, cost: 120, alert: 40 },
        { unit: 'carton', mode: 'bundle', pack: 'Pack12', price: 2700, cost: 1400, alert: 8 }
      ]},
      { name: 'Dentifrice Colgate', ref: 'COLGATE', desc: 'Dentifrice menthe', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Tube100ml', price: 800, cost: 480, alert: 25 },
        { unit: 'carton', mode: 'bundle', pack: 'Pack6', price: 4200, cost: 2600, alert: 6 }
      ]},
      { name: 'Shampoing Dove', ref: 'DOVE-SH', desc: 'Shampoing hydratant', variants: [
        { unit: 'piece', mode: 'unit', pack: '400ml', price: 2200, cost: 1400, alert: 15 }
      ]},
      { name: 'Gel douche Nivea', ref: 'NIVEA-GD', desc: 'Gel douche', variants: [
        { unit: 'piece', mode: 'unit', pack: '250ml', price: 1500, cost: 900, alert: 18 },
        { unit: 'liter', mode: 'volume', pack: '500ml', price: 2500, cost: 1500, alert: 12 }
      ]},
      { name: 'Papier toilette', ref: 'PQ', desc: 'Papier hygiénique', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Rouleau', price: 150, cost: 70, alert: 80 },
        { unit: 'carton', mode: 'bundle', pack: 'Pack12', price: 1600, cost: 850, alert: 15 }
      ]},
      { name: 'Lessive Omo', ref: 'OMO', desc: 'Lessive en poudre', variants: [
        { unit: 'kg', mode: 'weight', pack: '1kg', price: 1800, cost: 1100, alert: 20 },
        { unit: 'piece', mode: 'unit', pack: '3kg', price: 4800, cost: 3000, alert: 8 }
      ]},
      { name: 'Javel', ref: 'JAVEL', desc: 'Eau de javel', variants: [
        { unit: 'liter', mode: 'volume', pack: '1L', price: 400, cost: 200, alert: 25 },
        { unit: 'piece', mode: 'unit', pack: '5L', price: 1500, cost: 850, alert: 10 }
      ]},
      { name: 'Couches bébé', ref: 'COUCHES', desc: 'Couches jetables taille M', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Pack30', price: 4500, cost: 2800, alert: 12 },
        { unit: 'carton', mode: 'bundle', pack: 'Pack60', price: 8000, cost: 5200, alert: 6 }
      ]},
      { name: 'Rasoir jetable', ref: 'RASOIR', desc: 'Rasoirs jetables', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Pack5', price: 1200, cost: 700, alert: 20 }
      ]},
      { name: 'Déodorant Rexona', ref: 'REXONA', desc: 'Déodorant spray', variants: [
        { unit: 'piece', mode: 'unit', pack: '150ml', price: 1800, cost: 1100, alert: 15 }
      ]},
      { name: 'Serviettes hygiéniques', ref: 'SERVIETTE', desc: 'Serviettes Always', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Pack10', price: 900, cost: 520, alert: 22 }
      ]},
      { name: 'Brosse à dents', ref: 'BROSSE', desc: 'Brosse à dents souple', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Unité', price: 500, cost: 250, alert: 30 },
        { unit: 'carton', mode: 'bundle', pack: 'Pack12', price: 5200, cost: 2800, alert: 5 }
      ]},
      { name: 'Crème hydratante', ref: 'CREME', desc: 'Crème corps', variants: [
        { unit: 'piece', mode: 'unit', pack: '200ml', price: 2500, cost: 1500, alert: 12 }
      ]}
    ]
  },
  {
    name: 'Snacks & Confiserie',
    products: [
      { name: 'Biscuits Banania', ref: 'BANANIA', desc: 'Biscuits chocolatés', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Paquet', price: 600, cost: 350, alert: 35 },
        { unit: 'carton', mode: 'bundle', pack: 'Carton24', price: 13000, cost: 8000, alert: 6 }
      ]},
      { name: 'Chips Pringles', ref: 'PRINGLES', desc: 'Chips tube', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Tube', price: 1800, cost: 1100, alert: 20 }
      ]},
      { name: 'Chocolat Mentos', ref: 'MENTOS', desc: 'Bonbons Mentos', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Rouleau', price: 200, cost: 100, alert: 50 },
        { unit: 'carton', mode: 'bundle', pack: 'Display40', price: 7000, cost: 4000, alert: 8 }
      ]},
      { name: 'Gâteaux Oreo', ref: 'OREO', desc: 'Biscuits Oreo', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Paquet', price: 900, cost: 520, alert: 28 },
        { unit: 'carton', mode: 'bundle', pack: 'Carton12', price: 9800, cost: 6000, alert: 5 }
      ]},
      { name: 'Cacahuètes grillées', ref: 'CACAHUETE', desc: 'Cacahuètes salées', variants: [
        { unit: 'piece', mode: 'unit', pack: '250g', price: 700, cost: 400, alert: 25 },
        { unit: 'kg', mode: 'weight', pack: '1kg', price: 2400, cost: 1400, alert: 12 }
      ]},
      { name: 'Bonbons Mimie', ref: 'MIMIE', desc: 'Bonbons assortis', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Sachet', price: 150, cost: 70, alert: 60 }
      ]},
      { name: 'Popcorn', ref: 'POPCORN', desc: 'Maïs à éclater', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Sachet', price: 500, cost: 280, alert: 30 }
      ]},
      { name: 'Gaufrettes', ref: 'GAUFRETTE', desc: 'Gaufrettes fourrées', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Paquet', price: 450, cost: 250, alert: 35 },
        { unit: 'carton', mode: 'bundle', pack: 'Carton20', price: 8000, cost: 4800, alert: 6 }
      ]},
      { name: 'Chewing-gum', ref: 'CHEWING', desc: 'Chewing-gum menthe', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Plaquette', price: 100, cost: 45, alert: 80 }
      ]},
      { name: 'Barre chocolatée', ref: 'SNICKERS', desc: 'Barre Snickers', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Unité', price: 350, cost: 200, alert: 45 },
        { unit: 'carton', mode: 'bundle', pack: 'Box24', price: 7500, cost: 4500, alert: 7 }
      ]},
      { name: 'Crackers salés', ref: 'CRACKERS', desc: 'Crackers apéritifs', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Paquet', price: 550, cost: 300, alert: 30 }
      ]},
      { name: 'Confiserie caramels', ref: 'CARAMEL', desc: 'Caramels mous', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Sachet200g', price: 800, cost: 450, alert: 20 }
      ]},
      { name: 'Jus en sachet Capri', ref: 'CAPRI', desc: 'Jus Capri-Sun', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Sachet', price: 200, cost: 100, alert: 50 },
        { unit: 'carton', mode: 'bundle', pack: 'Pack10', price: 1800, cost: 1000, alert: 12 }
      ]},
      { name: 'Glace Magnum', ref: 'MAGNUM', desc: 'Glace bâtonnet (surgelé)', variants: [
        { unit: 'piece', mode: 'unit', pack: 'Unité', price: 700, cost: 400, alert: 25 },
        { unit: 'carton', mode: 'bundle', pack: 'Box20', price: 12000, cost: 7500, alert: 5 }
      ]}
    ]
  }
]

const lines = []
const push = (s = '') => lines.push(s)

push('-- =============================================================================')
push('-- Stockify mono — seed catalogue / stock réaliste')
push('-- Période mouvements : 2026-04-11 → 2026-07-11')
push('-- Généré par : scripts/generate-realistic-seed.mjs')
push('--')
push('-- Prérequis : schéma migré, MySQL 8+')
push('-- Usage :')
push('--   mysql -uroot stockify_mono < seeds/realistic_catalog_seed.sql')
push('--')
push('-- Attention : vide les tables catalogue + stock (conserve users / refresh_tokens).')
push('-- =============================================================================')
push('')
push('SET NAMES utf8mb4;')
push('SET FOREIGN_KEY_CHECKS = 0;')
push('SET sql_mode = \'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION\';')
push('')
push('-- Purge inventory + catalog (ordre respectant les FKs)')
push('DELETE FROM stock_lot_allocations;')
push('DELETE FROM stock_movements;')
push('DELETE FROM stock_lots;')
push('DELETE FROM stock_policies;')
push('DELETE FROM product_variants;')
push('DELETE FROM products;')
push('DELETE FROM product_categories;')
push('DELETE FROM units_of_measure;')
push('')
push('-- Keep FK checks off until the end for faster bulk load')
push('')
push('-- -----------------------------------------------------------------------------')
push('-- Unités de mesure')
push('-- -----------------------------------------------------------------------------')
for (const u of UNITS) {
  push(
    `INSERT INTO units_of_measure (id, code, label, decimal_places, is_system) VALUES (${bin(u.id)}, '${u.code}', '${esc(u.label)}', ${u.decimals}, 1);`
  )
}
push('')

const now = fmtDt(TODAY)
const categories = []
const products = []
const variants = []
const policies = []
const lots = []
const movements = []
const allocations = []

let sortOrder = 0
for (const cat of CATALOG) {
  const catId = makeUuid()
  const catSort = sortOrder++
  categories.push({ id: catId, name: cat.name, sortOrder: catSort })
  push(
    `INSERT INTO product_categories (id, name, sort_order, status, parent_id, created_at, updated_at) VALUES (${bin(catId)}, '${esc(cat.name)}', ${catSort}, 'active', NULL, '${now}', '${now}');`
  )

  for (const p of cat.products) {
    const productId = makeUuid()
    const createdAt = fmtDt(addDays(PERIOD_START, rndInt(0, 20)))
    products.push({ id: productId, categoryId: catId, ...p })
    push(
      `INSERT INTO products (id, name, reference, description, status, category_id, created_at, updated_at) VALUES (${bin(productId)}, '${esc(p.name)}', '${esc(p.ref)}', ${sqlStr(p.desc)}, 'active', ${bin(catId)}, '${createdAt}', '${createdAt}');`
    )

    // Unique (unit, sale_mode) per product — required by DB constraint
    const usedCombos = new Set()
    p.variants.forEach((v) => {
      let unit = v.unit
      let mode = v.mode
      let combo = `${unit}|${mode}`
      if (usedCombos.has(combo)) {
        throw new Error(`Duplicate variant combo for ${p.ref}: ${combo} (${v.pack})`)
      }
      usedCombos.add(combo)

      const variantId = makeUuid()
      const sku = `${p.ref}-${v.pack}`.toUpperCase().replace(/[^A-Z0-9]+/g, '-').slice(0, 100)
      const unitId = UNIT_IDS[unit]
      const vCreated = createdAt
      variants.push({
        id: variantId,
        productId,
        sku,
        unit,
        mode,
        price: v.price,
        cost: v.cost,
        alert: v.alert,
        pack: v.pack,
        productName: p.name,
        ref: p.ref
      })

      push(
        `INSERT INTO product_variants (id, sku, sale_mode, default_price, alert_threshold, status, product_id, unit_of_measure_id, created_at, updated_at) VALUES (${bin(variantId)}, '${esc(sku)}', '${mode}', '${sqlDec(v.price, 2)}', '${sqlDec(v.alert, 3)}', 'active', ${bin(productId)}, ${bin(unitId)}, '${vCreated}', '${vCreated}');`
      )

      const policyId = makeUuid()
      policies.push(policyId)
      push(
        `INSERT INTO stock_policies (id, strategy, variant_id, created_at, updated_at) VALUES (${bin(policyId)}, 'fifo', ${bin(variantId)}, '${vCreated}', '${vCreated}');`
      )

      // 1–3 lots over the period
      const lotCount = rndInt(1, 3)
      const lotRecords = []
      for (let li = 0; li < lotCount; li++) {
        const lotId = makeUuid()
        const receivedAtDate = addDays(PERIOD_START, rndInt(li * 20, Math.min(85, 25 + li * 30)))
        // Clamp to period
        const receivedAt = receivedAtDate > TODAY ? addDays(TODAY, -rndInt(1, 10)) : receivedAtDate
        receivedAt.setHours(8 + rndInt(0, 8), rndInt(0, 59), rndInt(0, 59))

        const qtyInitial = rndInt(40, 220) + (unit === 'carton' ? rndInt(5, 40) : 0)
        const unitCost = rndFloat(v.cost * 0.92, v.cost * 1.08, 4)
        const lotRef = `LOT-${p.ref}-${String(li + 1).padStart(2, '0')}`
        const supplier = rndPick(['Fournisseur Central', 'SODISCAM', 'DistriPlus', 'Import Afrique', 'Grossiste Marché'])
        const hasExpiry = ['Boissons', 'Snacks & Confiserie', 'Épicerie'].includes(cat.name) && rnd() > 0.35
        const expiry = hasExpiry ? fmtDate(addDays(receivedAt, rndInt(90, 360))) : null

        const lot = {
          id: lotId,
          variantId,
          qtyInitial,
          qtyRemaining: qtyInitial,
          unitCost,
          receivedAt,
          reference: lotRef,
          supplier,
          expiry
        }
        lotRecords.push(lot)
        lots.push(lot)

        push(
          `INSERT INTO stock_lots (id, reference, quantity_initial, quantity_remaining, unit_cost, received_at, supplier_ref, expiry_date, variant_id, created_at, updated_at) VALUES (${bin(lotId)}, '${esc(lotRef)}', '${sqlDec(qtyInitial, 3)}', '${sqlDec(qtyInitial, 3)}', '${sqlDec(unitCost, 4)}', '${fmtDt(receivedAt)}', '${esc(supplier)}', ${expiry ? `'${expiry}'` : 'NULL'}, ${bin(variantId)}, '${fmtDt(receivedAt)}', '${fmtDt(receivedAt)}');`
        )

        // Purchase IN movement + allocation for this receipt
        const movId = makeUuid()
        const movAt = receivedAt
        movements.push({ id: movId, variantId, type: 'purchase', direction: 'in', qty: qtyInitial, unitCost, at: movAt })
        push(
          `INSERT INTO stock_movements (id, type, direction, quantity, unit_cost, reason, source_ref, occurred_at, created_at, variant_id, performed_by_id) VALUES (${bin(movId)}, 'purchase', 'in', '${sqlDec(qtyInitial, 3)}', '${sqlDec(unitCost, 4)}', 'Réception fournisseur', ${sqlStr(lotRef)}, '${fmtDt(movAt)}', '${fmtDt(movAt)}', ${bin(variantId)}, NULL);`
        )
        const allocId = makeUuid()
        allocations.push(allocId)
        push(
          `INSERT INTO stock_lot_allocations (id, quantity, unit_cost, movement_id, lot_id) VALUES (${bin(allocId)}, '${sqlDec(qtyInitial, 3)}', '${sqlDec(unitCost, 4)}', ${bin(movId)}, ${bin(lotId)});`
        )
      }

      // Sales / adjustments OUT after lots exist — FIFO consume
      lotRecords.sort((a, b) => a.receivedAt - b.receivedAt)
      const salesCount = rndInt(2, 6)
      for (let s = 0; s < salesCount; s++) {
        const available = lotRecords.reduce((sum, l) => sum + l.qtyRemaining, 0)
        if (available < 2) break

        const maxOut = Math.min(available - 1, Math.max(1, Math.floor(available * 0.35)))
        const outQty = rndInt(1, Math.max(1, maxOut))
        const saleAt = (() => {
          const earliest = lotRecords[0].receivedAt
          const span = TODAY - earliest
          if (span <= 0) return addHours(earliest, 2)
          return new Date(earliest.getTime() + Math.floor(rnd() * span))
        })()
        if (saleAt <= lotRecords[0].receivedAt) {
          saleAt.setTime(lotRecords[0].receivedAt.getTime() + 3600 * 1000 * rndInt(2, 48))
        }

        const movId = makeUuid()
        const isAdjust = rnd() < 0.15
        const type = isAdjust ? 'adjustment' : 'sale'
        const reason = isAdjust
          ? rndPick(['Inventaire', 'Casse', 'Perte', 'Correction stock'])
          : rndPick(['Vente comptoir', 'Vente caisse', 'Commande client', 'Vente détail'])

        movements.push({ id: movId, variantId, type, direction: 'out', qty: outQty, at: saleAt })
        push(
          `INSERT INTO stock_movements (id, type, direction, quantity, unit_cost, reason, source_ref, occurred_at, created_at, variant_id, performed_by_id) VALUES (${bin(movId)}, '${type}', 'out', '${sqlDec(outQty, 3)}', NULL, ${sqlStr(reason)}, NULL, '${fmtDt(saleAt)}', '${fmtDt(saleAt)}', ${bin(variantId)}, NULL);`
        )

        let remaining = outQty
        for (const lot of lotRecords) {
          if (remaining <= 0) break
          if (lot.qtyRemaining <= 0) continue
          const take = Math.min(lot.qtyRemaining, remaining)
          lot.qtyRemaining -= take
          remaining -= take
          const allocId = makeUuid()
          allocations.push(allocId)
          push(
            `INSERT INTO stock_lot_allocations (id, quantity, unit_cost, movement_id, lot_id) VALUES (${bin(allocId)}, '${sqlDec(take, 3)}', '${sqlDec(lot.unitCost, 4)}', ${bin(movId)}, ${bin(lot.id)});`
          )
        }
      }

      // Update lot remaining quantities to match FIFO consumption
      for (const lot of lotRecords) {
        push(
          `UPDATE stock_lots SET quantity_remaining = '${sqlDec(lot.qtyRemaining, 3)}', updated_at = '${now}' WHERE id = ${bin(lot.id)};`
        )
      }

      // Occasionally leave a variant near/under alert threshold for demo
      if (rnd() < 0.12) {
        const lastLot = lotRecords[lotRecords.length - 1]
        if (lastLot && lastLot.qtyRemaining > v.alert) {
          const target = Math.max(0, Math.floor(v.alert * rndFloat(0.3, 0.9, 2)))
          const drop = lastLot.qtyRemaining - target
          if (drop > 0) {
            lastLot.qtyRemaining = target
            const movId = makeUuid()
            const at = addDays(TODAY, -rndInt(0, 5))
            at.setHours(10, rndInt(0, 50), 0)
            push(
              `INSERT INTO stock_movements (id, type, direction, quantity, unit_cost, reason, source_ref, occurred_at, created_at, variant_id, performed_by_id) VALUES (${bin(movId)}, 'sale', 'out', '${sqlDec(drop, 3)}', NULL, 'Vente forte — stock bas', NULL, '${fmtDt(at)}', '${fmtDt(at)}', ${bin(variantId)}, NULL);`
            )
            const allocId = makeUuid()
            push(
              `INSERT INTO stock_lot_allocations (id, quantity, unit_cost, movement_id, lot_id) VALUES (${bin(allocId)}, '${sqlDec(drop, 3)}', '${sqlDec(lastLot.unitCost, 4)}', ${bin(movId)}, ${bin(lastLot.id)});`
            )
            push(
              `UPDATE stock_lots SET quantity_remaining = '${sqlDec(lastLot.qtyRemaining, 3)}', updated_at = '${now}' WHERE id = ${bin(lastLot.id)};`
            )
          }
        }
      }
    })
  }
  push('')
}

push('')
push('-- -----------------------------------------------------------------------------')
push('-- Résumé (commentaires)')
push(`-- Catégories : ${categories.length}`)
push(`-- Produits   : ${products.length}`)
push(`-- Variantes  : ${variants.length}`)
push(`-- Lots       : ${lots.length}`)
push(`-- Mouvements : ${movements.length} (+ updates lots)`)
push('-- -----------------------------------------------------------------------------')
push('')
push('SET FOREIGN_KEY_CHECKS = 1;')
push('')
push('SELECT')
push("  (SELECT COUNT(*) FROM product_categories) AS categories,")
push("  (SELECT COUNT(*) FROM products) AS products,")
push("  (SELECT COUNT(*) FROM product_variants) AS variants,")
push("  (SELECT COUNT(*) FROM stock_lots) AS lots,")
push("  (SELECT COUNT(*) FROM stock_movements) AS movements,")
push("  (SELECT COUNT(*) FROM stock_lot_allocations) AS allocations;")

mkdirSync(dirname(outPath), { recursive: true })
writeFileSync(outPath, lines.join('\n'), 'utf8')

console.log(`Wrote ${outPath}`)
console.log({
  categories: categories.length,
  products: products.length,
  variants: variants.length,
  lots: lots.length,
  movements: movements.length,
  allocations: allocations.length,
  lines: lines.length
})
