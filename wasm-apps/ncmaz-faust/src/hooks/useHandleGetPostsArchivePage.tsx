import {
	OrderEnum,
	PostObjectsConnectionOrderbyEnum,
} from '@/__generated__/graphql'
import { FILTERS_OPTIONS, GET_POSTS_FIRST_COMMON } from '@/contains/contants'
import { PostDataFragmentType } from '@/data/types'
import { QUERY_GET_POSTS_BY } from '@/fragments/queries'
import errorHandling from '@/utils/errorHandling'
import updatePostFromUpdateQuery from '@/utils/updatePostFromUpdateQuery'
import { useLazyQuery } from '@apollo/client'
import React, { useEffect, useState } from 'react'

interface Props {
	initPosts?: PostDataFragmentType[] | null
	initPostsPageInfo?: {
		endCursor?: string | null | undefined
		hasNextPage: boolean
	} | null
	tagDatabaseId?: number | null
	categoryDatabaseId?: number | null
	authorDatabaseId?: number | null
	categorySlug?: string | null
	search?: string | null
	categoryIn?: number[] | null
	tagIn?: number[] | null
	authorIn?: number[] | null
	keyword?: string | null
}

export default function useHandleGetPostsArchivePage(props: Props) {
	const {
		categoryDatabaseId,
		initPosts: posts,
		initPostsPageInfo,
		tagDatabaseId,
		authorDatabaseId,
		categorySlug,
		search,
		categoryIn,
		tagIn,
		authorIn,
		keyword,
	} = props

	const [filterParam, setfilterParam] =
		useState<`${PostObjectsConnectionOrderbyEnum}/${OrderEnum}`>()

	const [refetchTimes, setRefetchTimes] = useState(0)

	const routerQueryFilter = filterParam
	const searchValue = keyword ?? search ?? ''
	const categoryInIds = categoryIn?.map((id) => id.toString()) ?? null
	const tagInIds = tagIn?.map((id) => id.toString()) ?? null
	const authorInIds = authorIn?.map((id) => id.toString()) ?? null
	const hasClientFilters =
		!!(categoryInIds && categoryInIds.length) ||
		!!(tagInIds && tagInIds.length) ||
		!!(authorInIds && authorInIds.length) ||
		!!searchValue
	const fetchContext = {
		fetchOptions: {
			method: process.env.NEXT_PUBLIC_SITE_API_METHOD || 'GET',
		},
	}

	const [queryGetPostsByCategoryId, postsByCategoryIdResult] = useLazyQuery(
		QUERY_GET_POSTS_BY,
		{
			notifyOnNetworkStatusChange: true,
		},
	)

	useEffect(() => {
		if (!postsByCategoryIdResult.error) return
		if (refetchTimes > 3) {
			errorHandling(postsByCategoryIdResult.error)
			return
		}
		setRefetchTimes((prev) => prev + 1)
		const fiterValue = checkRouterQueryFilter()
		const field = fiterValue
			? fiterValue.field
			: PostObjectsConnectionOrderbyEnum.Date
		const order = fiterValue ? fiterValue.order : OrderEnum.Desc
		queryGetPostsByCategoryId({
			variables: {
				first: GET_POSTS_FIRST_COMMON,
				after: '',
				field,
				order,
				categoryIn: categoryInIds || undefined,
				tagIn: tagInIds || undefined,
				authorIn: authorInIds || undefined,
				search: searchValue,
			},
			context: fetchContext,
		})
	}, [
		postsByCategoryIdResult.error,
		refetchTimes,
		categoryInIds,
		tagInIds,
		authorInIds,
		searchValue,
		routerQueryFilter,
		hasClientFilters,
	])

	function checkRouterQueryFilter(): {
		field: PostObjectsConnectionOrderbyEnum
		order: OrderEnum
	} | null {
		// tra ve false neu khong co filter/ lan dau tien vao trang  / khi chua click vao filter nao
		if (!routerQueryFilter) {
			return null
		}

		const [field, order] = routerQueryFilter?.split('/')
		return {
			field: field as PostObjectsConnectionOrderbyEnum,
			order: order as OrderEnum,
		}
	}

	// get posts by category id  and by filter
	useEffect(() => {
		if (!routerQueryFilter && !hasClientFilters) {
			return
		}
		const fiterValue = checkRouterQueryFilter()
		const field = fiterValue
			? fiterValue.field
			: PostObjectsConnectionOrderbyEnum.Date
		const order = fiterValue ? fiterValue.order : OrderEnum.Desc

		queryGetPostsByCategoryId({
			variables: {
				first: GET_POSTS_FIRST_COMMON,
				after: '',
				field,
				order,
				categoryIn: categoryInIds || undefined,
				tagIn: tagInIds || undefined,
				authorIn: authorInIds || undefined,
				search: searchValue,
			},
			context: fetchContext,
		})
	}, [
		routerQueryFilter,
		hasClientFilters,
		categoryInIds,
		tagInIds,
		authorInIds,
		searchValue,
	])

	const handleClickShowMore = () => {
		// Articles tab
		if (!postsByCategoryIdResult.called) {
			queryGetPostsByCategoryId({
				variables: {
					after: initPostsPageInfo?.endCursor,
					first: GET_POSTS_FIRST_COMMON,
				},
				context: fetchContext,
			})
		} else {
			postsByCategoryIdResult.fetchMore({
				variables: {
					after: postsByCategoryIdResult.data?.posts?.pageInfo?.endCursor,
					first: GET_POSTS_FIRST_COMMON,
				},
				context: fetchContext,
				updateQuery: (prev, { fetchMoreResult }) => {
					return updatePostFromUpdateQuery(prev, fetchMoreResult)
				},
			})
		}
	}

	const handleChangeFilterPosts = (item: (typeof FILTERS_OPTIONS)[number]) => {
		setfilterParam(item.value)
	}

	//  data for render
	let loading = postsByCategoryIdResult.loading
	let currentPosts = posts || []
	let hasNextPage = !!initPostsPageInfo?.hasNextPage
	currentPosts = [
		...(!checkRouterQueryFilter() ? posts || [] : []),
		...((postsByCategoryIdResult.data?.posts
			?.nodes as PostDataFragmentType[]) || []),
	]

	// hien thi init posts khi lan dau tien click vao filter mac dinh la DATE/DESC
	if (!currentPosts.length && loading && filterParam === 'DATE/DESC') {
		currentPosts = posts || []
	}

	if (postsByCategoryIdResult.called) {
		hasNextPage =
			!!postsByCategoryIdResult.data?.posts?.pageInfo?.hasNextPage ||
			postsByCategoryIdResult.loading
	}

	return {
		loading,
		currentPosts,
		hasNextPage,
		handleClickShowMore,
		handleChangeFilterPosts,
	}
}
