import fs from 'node:fs'
import path from 'node:path'
import process from 'node:process'
import { fileURLToPath } from 'node:url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)
const rootDir = path.resolve(__dirname, '..')
const shopsRoot = path.join(rootDir, 'shop-configs')
const publicDir = path.join(rootDir, 'public')
const srcAssetsDir = path.join(rootDir, 'src', 'assets')
const generatedConfigPath = path.join(rootDir, 'src', 'generated', 'shop-config.generated.js')

function parseArgs(argv) {
  const data = {}

  for (let i = 0; i < argv.length; i += 1) {
    const token = argv[i]
    if (!token.startsWith('--')) continue

    const [key, value] = token.split('=')
    if (value !== undefined) {
      data[key.slice(2)] = value
      continue
    }

    const next = argv[i + 1]
    if (next && !next.startsWith('--')) {
      data[key.slice(2)] = next
      i += 1
    } else {
      data[key.slice(2)] = true
    }
  }

  return data
}

const SUPPORTED_CONFIG_ENVS = new Set(['dev', 'prod'])

function ensureShopId(value) {
  if (!value || typeof value !== 'string') {
    throw new Error('Missing shop id. Use --shop=<id>.')
  }

  const safe = value.trim()
  if (!/^[a-zA-Z0-9_-]+$/.test(safe)) {
    throw new Error(`Invalid shop id "${value}". Allowed chars: a-z, A-Z, 0-9, _, -`)
  }

  return safe
}

function ensureConfigEnv(value) {
  const safe = String(value || '').trim().toLowerCase()
  if (!SUPPORTED_CONFIG_ENVS.has(safe)) {
    throw new Error(`Invalid config env "${value}". Use dev or prod.`)
  }

  return safe
}

function resolveConfigEnv(args) {
  const fromArgs = typeof args.env === 'string' ? args.env : ''
  const fromEnv = process.env.SHOP_ENV || process.env.BUILD_ENV || ''
  return ensureConfigEnv(fromArgs || fromEnv || 'dev')
}

function resolveConfigPath(shopDir, configEnv) {
  const envConfigPath = path.join(shopDir, `config.${configEnv}.json`)
  const legacyConfigPath = path.join(shopDir, 'config.json')

  if (fs.existsSync(envConfigPath)) {
    return envConfigPath
  }

  if (fs.existsSync(legacyConfigPath)) {
    console.warn(
      `[shop] config.${configEnv}.json not found in ${path.basename(shopDir)}, falling back to config.json.`
    )
    return legacyConfigPath
  }

  throw new Error(
    `Config not found for shop "${path.basename(shopDir)}" (env=${configEnv}). `
    + `Expected ${envConfigPath} or ${legacyConfigPath}.`
  )
}

function readJsonFile(filePath) {
  if (!fs.existsSync(filePath)) {
    throw new Error(`File not found: ${filePath}`)
  }

  return JSON.parse(fs.readFileSync(filePath, 'utf8'))
}

function validateConfig(shopId, config) {
  const requiredStringFields = [
    'id',
    'displayName',
    'appTitle',
    'brandName',
    'brandSubtitle',
    'viteApiPrefix'
  ]

  for (const field of requiredStringFields) {
    if (typeof config[field] !== 'string' || config[field].trim() === '') {
      throw new Error(`Invalid config for shop "${shopId}": "${field}" must be a non-empty string.`)
    }
  }

  if (config.id !== shopId) {
    console.warn(`[shop] Config id mismatch in ${shopId}: got "${config.id}", using "${shopId}".`)
    config.id = shopId
  }
}

function normalizeRelativePath(value) {
  return String(value || '').replace(/\\/g, '/').replace(/^\/+/, '').trim()
}

function isSafeRelativePath(value) {
  return value !== '' && !value.startsWith('..') && !value.includes('/../')
}

function fileExists(filePath) {
  return fs.existsSync(filePath) && fs.statSync(filePath).isFile()
}

function copyRelativeFile(sourceRoot, targetRoot, sourceRelativePath, targetRelativePath = sourceRelativePath) {
  const sourceRel = normalizeRelativePath(sourceRelativePath)
  const targetRel = normalizeRelativePath(targetRelativePath)

  if (!isSafeRelativePath(sourceRel) || !isSafeRelativePath(targetRel)) {
    return false
  }

  const from = path.join(sourceRoot, sourceRel)
  if (!fileExists(from)) {
    return false
  }

  const to = path.join(targetRoot, targetRel)
  fs.mkdirSync(path.dirname(to), { recursive: true })
  fs.copyFileSync(from, to)
  return true
}

function resetSyncedPublicDir() {
  const preserve = new Set(['.htaccess'])

  if (!fs.existsSync(publicDir)) {
    fs.mkdirSync(publicDir, { recursive: true })
    return
  }

  for (const entry of fs.readdirSync(publicDir, { withFileTypes: true })) {
    if (preserve.has(entry.name)) {
      continue
    }

    fs.rmSync(path.join(publicDir, entry.name), { recursive: true, force: true })
  }
}

function validateRequiredAssets(shopId, config, shopPublicDir) {
  const missing = []

  for (const [key, value] of Object.entries(config.brandingAssets || {})) {
    if (typeof value !== 'string') {
      continue
    }

    const relativePath = normalizeRelativePath(value)
    if (!relativePath || !isSafeRelativePath(relativePath)) {
      continue
    }

    if (!fileExists(path.join(shopPublicDir, relativePath))) {
      missing.push(`brandingAssets.${key} (${relativePath})`)
    }
  }

  if (missing.length > 0) {
    throw new Error(
      `Missing required assets for shop "${shopId}": ${missing.join(', ')}. `
      + `Add them under shop-configs/${shopId}/public/.`
    )
  }
}

function syncShopAssets(config, shopPublicDir) {
  if (!fs.existsSync(shopPublicDir) || !fs.statSync(shopPublicDir).isDirectory()) {
    throw new Error(`Shop public directory not found: ${shopPublicDir}`)
  }

  resetSyncedPublicDir()

  const brandingFiles = Object.values(config.brandingAssets || {})
    .filter((value) => typeof value === 'string')
    .map((value) => normalizeRelativePath(value))
    .filter((value) => isSafeRelativePath(value))

  let copiedToPublic = 0
  let copiedToAssets = 0

  for (const relativeFile of brandingFiles) {
    if (copyRelativeFile(shopPublicDir, publicDir, relativeFile)) {
      copiedToPublic += 1
    }
    if (copyRelativeFile(shopPublicDir, srcAssetsDir, relativeFile)) {
      copiedToAssets += 1
    }
  }

  return { copiedToPublic, copiedToAssets }
}

function generateModuleSource(config) {
  return `const shopConfig = ${JSON.stringify(config, null, 4)};\n\nexport default shopConfig;\n`
}

function main() {
  const args = parseArgs(process.argv.slice(2))
  const fromArgs = typeof args.shop === 'string' ? args.shop : ''
  const fromEnv = process.env.SHOP || process.env.npm_config_shop || ''
  const shopId = ensureShopId(fromArgs || fromEnv || 'default')
  const configEnv = resolveConfigEnv(args)

  const shopDir = path.join(shopsRoot, shopId)
  const configPath = resolveConfigPath(shopDir, configEnv)
  const shopPublicDir = path.join(shopDir, 'public')

  const config = readJsonFile(configPath)
  validateConfig(shopId, config)
  validateRequiredAssets(shopId, config, shopPublicDir)

  const syncResult = syncShopAssets(config, shopPublicDir)

  fs.mkdirSync(path.dirname(generatedConfigPath), { recursive: true })
  fs.writeFileSync(generatedConfigPath, generateModuleSource(config), 'utf8')

  console.log(`[shop] Active shop: ${shopId} (${configEnv})`)
  console.log(`[shop] Config source: ${path.relative(rootDir, configPath)}`)
  console.log(`[shop] Generated: ${path.relative(rootDir, generatedConfigPath)}`)
  console.log(`[shop] Assets synced: public (${syncResult.copiedToPublic}), src/assets (${syncResult.copiedToAssets})`)
}

main()
