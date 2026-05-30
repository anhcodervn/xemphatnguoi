import { useRouter } from 'vue-router'

type GoToPageType = {
  nameRoute: string
  params?: Record<string, any>
  query?: Record<string, any>
}

export const useNavigation = () => {
  const router = useRouter()

  const gotoPage = ({ nameRoute, params, query }: GoToPageType) => {
    router.push({
      name: nameRoute,
      params,
      query
    })
  }

  return { gotoPage }
}