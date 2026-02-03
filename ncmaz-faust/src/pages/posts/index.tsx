import { FaustPage } from '@faustwp/core'
import { getNextStaticPropsNoISR } from '@/utils/getNextStaticPropsNoISR'
import { gql } from '@/__generated__'
import {
	NcgeneralSettingsFieldsFragmentFragment,
	OrderEnum,
	PostObjectsConnectionOrderbyEnum,
	PostsFilterPageQueryGetPostsQuery,
} from '@/__generated__/graphql'
import { FILTERS_OPTIONS } from '@/contains/contants'
import React, { useState } from 'react'
import { FOOTER_LOCATION, PRIMARY_LOCATION } from '@/contains/menu'
import PageLayout from '@/container/PageLayout'
import { PostDataFragmentType } from '@/data/types'
import GridPostsArchive from '@/components/GridPostsArchive'
import useGetPostsNcmazMetaByIds from '@/hooks/useGetPostsNcmazMetaByIds'
import { TPostCard } from '@/components/Card2/Card2'
import { GetServerSidePropsContext } from 'next'
import TabFilters from '@/components/TabFilters'
import getTrans from '@/utils/getTrans'
import { FireIcon } from '@/components/Icons/Icons'
import ArchiveFilterListBox from '@/components/ArchiveFilterListBox/ArchiveFilterListBox'
import useHandleGetPostsArchivePage from '@/hooks/useHandleGetPostsArchivePage'

const T = getTrans()
const GET_POSTS_FIRST_COMMON = 24

interface ConTextQuery {
	search: string | null
	tagIn: number[]
	authorIn: number[]
	categoryIn: number[]
	field: string
	order: string
	first: number | null
	last: number | null
	after: string | null
	before: string | null
}

const Page: FaustPage<PostsFilterPageQueryGetPostsQuery> = (props) => {
	const { posts } = props.data || {}

	//
	const {} = useGetPostsNcmazMetaByIds({
		posts: (posts?.nodes || []) as TPostCard[],
	})
	//

	const initPosts = (posts?.nodes as PostDataFragmentType[]) || []
	const ctxQuery: ConTextQuery = props.__PAGE_VARIABLES__?.ctxQuery || {}
	const [categoryIn, setCategoryIn] = useState<number[]>(
		ctxQuery.categoryIn || [],
	)
	const [tagIn, setTagIn] = useState<number[]>(ctxQuery.tagIn || [])
	const [authorIn, setAuthorIn] = useState<number[]>(ctxQuery.authorIn || [])
	const [keyword, setKeyword] = useState<string>(ctxQuery.search || '')

	const {
		currentPosts,
		handleChangeFilterPosts,
		handleClickShowMore,
		hasNextPage,
		loading,
	} = useHandleGetPostsArchivePage({
		initPosts,
		initPostsPageInfo: posts?.pageInfo || null,
		categoryIn,
		tagIn,
		authorIn,
		keyword,
	})

	const onCategoriesUpdated = (ids: number[]) => {
		setCategoryIn(ids)
	}

	const onTagsUpdated = (ids: number[]) => {
		setTagIn(ids)
	}

	const onAuthorsUpdated = (ids: number[]) => {
		setAuthorIn(ids)
	}

	const onKeywordUpdated = (value: string) => {
		setKeyword(value)
	}

	function checkRouterQueryFilter(
		routerQueryFilter: `${PostObjectsConnectionOrderbyEnum}/${OrderEnum}`,
	) {
		// tra ve false neu khong co filter/ lan dau tien vao trang  / khi chua click vao filter nao
		if (!routerQueryFilter) {
			return false
		}

		const [field, order] = routerQueryFilter?.split('/')
		return {
			field: field as PostObjectsConnectionOrderbyEnum,
			order: order as OrderEnum,
		}
	}

	return (
		<>
			<PageLayout
				headerMenuItems={props.data?.primaryMenuItems?.nodes || []}
				footerMenuItems={props.data?.footerMenuItems?.nodes || []}
				pageFeaturedImageUrl={null}
				pageTitle={T.Posts}
				generalSettings={
					props.data?.generalSettings as NcgeneralSettingsFieldsFragmentFragment
				}
			>
				<div className={`nc-PageCollection`}>
					<div className="container space-y-16 py-10 sm:space-y-20 lg:space-y-28 lg:pb-28 lg:pt-20">
						<div className="space-y-10">
							<header>
								<div className="mb-2 flex items-center gap-2 text-sm font-medium text-neutral-500">
									<FireIcon className="h-6 w-6" />
									<span className="">{T['Explore']}</span>
								</div>
								<h1 className="block text-2xl font-semibold capitalize sm:text-3xl lg:text-4xl">
									{T['Posts']}
								</h1>
							</header>

							<hr className="border-slate-200 dark:border-slate-700" />

							<main>
								{/* TABS FILTER */}
								<div className="flex flex-col lg:flex-row lg:justify-between">
									<TabFilters
										initCatIds={ctxQuery.categoryIn}
										initTagIds={ctxQuery.tagIn}
										initAuthorIds={ctxQuery.authorIn || []}
										initKeyword={ctxQuery.search || ''}
										onCategoriesUpdated={onCategoriesUpdated}
										onTagsUpdated={onTagsUpdated}
										onAuthorsUpdated={onAuthorsUpdated}
										onKeywordUpdated={onKeywordUpdated}
									/>
									<div className="my-4 block w-full border-b border-neutral-300 lg:hidden dark:border-neutral-500" />
									<div className="flex justify-end">
										<ArchiveFilterListBox
											defaultValue={
												FILTERS_OPTIONS.filter(
													(item) =>
														item.value ==
														`${ctxQuery.field.toUpperCase()}/${ctxQuery.order.toUpperCase()}`,
												)[0]
											}
											onChange={(item) => {
												const fiterValue = checkRouterQueryFilter(item.value)
												if (!fiterValue) {
													return
												}
												handleChangeFilterPosts(item)
											}}
											lists={FILTERS_OPTIONS}
										/>
									</div>
								</div>

								{/* LOOP ITEMS */}
								<GridPostsArchive
									posts={currentPosts}
									loading={loading}
									showLoadmore={hasNextPage}
									onClickLoadmore={handleClickShowMore}
								/>
							</main>
						</div>

						{/* SUBCRIBES */}
					</div>
				</div>
			</PageLayout>
		</>
	)
}

Page.variables = (context) => {
	const { params, query = {} } = context as GetServerSidePropsContext
	// query: { search: 'x', tagIn: ['1'], authorIn: ['2'], categoryIn: ['3'], page: '1', order: 'desc', field: 'date' }

	const search = typeof query.search === 'string' ? query.search || null : null

	const tagIn = (
		Array.isArray(query.tagIn) ? query.tagIn : query.tagIn ? [query.tagIn] : []
	).map((id) => parseInt(id))
	const authorIn = (
		Array.isArray(query.authorIn)
			? query.authorIn
			: query.authorIn
				? [query.authorIn]
				: []
	).map((id) => parseInt(id))
	const categoryIn = (
		Array.isArray(query.categoryIn)
			? query.categoryIn
			: query.categoryIn
				? [query.categoryIn]
				: []
	).map((id) => parseInt(id))

	const field = typeof query.field === 'string' ? query.field : 'DATE'
	const order = typeof query.order === 'string' ? query.order : 'DESC'

	const after = typeof query.after === 'string' ? query.after || null : null
	const before = typeof query.before === 'string' ? query.before || null : null

	let first =
		typeof query.first === 'string' ? parseInt(query.first) || null : null
	let last =
		typeof query.last === 'string' ? parseInt(query.last) || null : null

	// lan dau request thi first = 20, last = null
	if (!after && !before) {
		first = GET_POSTS_FIRST_COMMON
		last = null
	}

	const ctxQuery: ConTextQuery = {
		...query,
		search,
		tagIn,
		authorIn,
		categoryIn,
		field,
		order,
		first: last ? null : first || GET_POSTS_FIRST_COMMON,
		last,
		after,
		before,
	}

	return {
		...ctxQuery,
		ctxQuery,
		headerLocation: PRIMARY_LOCATION,
		footerLocation: FOOTER_LOCATION,
	}
}

Page.query = gql(`
  query PostsFilterPageQueryGetPosts( 
	$in: [ID] = null
	$first: Int = 20
	$after: String = null
	$last: Int
	$before: String
	$author: Int = null
	$categoryId: Int = null
	$authorIn: [ID] = null
	$categoryIn: [ID] = null
	$tagIn: [ID] = null
	$categoryName: String = null
	$tagId: String = null
	$day: Int = null
	$month: Int = null
	$year: Int = null
	$search: String = ""
	$field: PostObjectsConnectionOrderbyEnum = DATE
	$order: OrderEnum = DESC
	$headerLocation: MenuLocationEnum!
	$footerLocation: MenuLocationEnum!) {
    posts(
      first: $first
      after: $after
	  last: $last
	  before: $before
      where: {
        in: $in
		author: $author
		authorIn: $authorIn
		categoryIn: $categoryIn
		tagIn: $tagIn
		categoryId: $categoryId
		categoryName: $categoryName
		tagId: $tagId
		dateQuery: { day: $day, month: $month, year: $year }
		search: $search
		orderby: { field: $field, order: $order }
      }
	) {
        nodes {
          ...NcmazFcPostCardFields
        }
        pageInfo {
			hasNextPage
			endCursor
			hasPreviousPage
			startCursor
        }
      }
     
    # common query for all page 
    generalSettings {
      ...NcgeneralSettingsFieldsFragment
    }
    primaryMenuItems: menuItems(where: { location:  $headerLocation  }, first: 80) {
      nodes {
        ...NcPrimaryMenuFieldsFragment
      }
    }
    footerMenuItems: menuItems(where: { location:  $footerLocation  }, first: 50) {
      nodes {
        ...NcFooterMenuFieldsFragment
      }
    }
    # end common query
  }
`)

export function getStaticProps(ctx: GetServerSidePropsContext) {
	return getNextStaticPropsNoISR(ctx as any, {
		Page,
	})
}

export default Page
