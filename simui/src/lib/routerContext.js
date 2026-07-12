let appRouter = null

export const setAppRouter = (router) => {
  appRouter = router
  return appRouter
}

export const getAppRouter = () => appRouter
