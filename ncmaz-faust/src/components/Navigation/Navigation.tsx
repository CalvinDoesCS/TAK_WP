import { FC } from 'react'
import { flatListToHierarchical } from '@faustwp/core'
import NavigationItem2 from './NavigationItem2'
import dynamic from 'next/dynamic'

const DynamicNavigationItem = dynamic(() => import('./NavigationItem'))

interface Props {
	className?: string
	menuItems: any[]
	variation?: 'nav1' | 'nav2'
	maxItemsToShow?: number
}

const Navigation: FC<Props> = ({
	className = 'flex',
	menuItems,
	variation,
	maxItemsToShow,
}) => {
	let menus = flatListToHierarchical(menuItems, {
		idKey: 'id',
		parentKey: 'parentId',
		childrenKey: 'children',
	}) as any[]

	if (maxItemsToShow) {
		menus = menus.slice(0, maxItemsToShow)
	}

	return (
		<ul className={`nc-Navigation items-center ${className}`}>
			{menus.map((item, i) =>
				variation === 'nav2' ? (
					<NavigationItem2 key={i} menuItem={item} />
				) : (
					<DynamicNavigationItem key={i} menuItem={item} />
				),
			)}
		</ul>
	)
}

export default Navigation
