import process from 'node:process'
import { spawnSync } from 'node:child_process'

function parseArgs(argv) {
  const data = { shop: '', env: '' }

  for (let i = 0; i < argv.length; i += 1) {
    const token = argv[i]

    if (token.startsWith('--shop=')) {
      data.shop = token.slice('--shop='.length)
      continue
    }

    if (token === '--shop') {
      const next = argv[i + 1]
      if (next && !next.startsWith('--')) {
        data.shop = next
        i += 1
      }
      continue
    }

    if (token.startsWith('--env=')) {
      data.env = token.slice('--env='.length)
      continue
    }

    if (token === '--env') {
      const next = argv[i + 1]
      if (next && !next.startsWith('--')) {
        data.env = next
        i += 1
      }
    }
  }

  return {
    shop: data.shop || process.env.SHOP || process.env.npm_config_shop || 'default',
    env: data.env || process.env.SHOP_ENV || process.env.BUILD_ENV || 'prod'
  }
}

function run(command, args, env) {
  const result = spawnSync(command, args, {
    stdio: 'inherit',
    env,
    shell: false
  })

  if (result.error) {
    throw result.error
  }

  if (typeof result.status === 'number' && result.status !== 0) {
    process.exit(result.status)
  }
}

function main() {
  const { shop, env: configEnv } = parseArgs(process.argv.slice(2))
  const env = {
    ...process.env,
    SHOP: shop,
    SHOP_ENV: configEnv,
    BUILD_ENV: configEnv
  }

  const nodeBin = process.execPath
  run(nodeBin, ['./scripts/select-shop.mjs', `--shop=${shop}`, `--env=${configEnv}`], env)
  run(nodeBin, ['./node_modules/vite/bin/vite.js', 'build'], env)
}

main()
