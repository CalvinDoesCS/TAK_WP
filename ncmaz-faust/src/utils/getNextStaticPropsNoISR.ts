import { getNextStaticProps } from '@faustwp/core'
import type { GetStaticPropsContext } from 'next'

export async function getNextStaticPropsNoISR(
	ctx: GetStaticPropsContext,
	cfg: any,
) {
	const result = await getNextStaticProps(ctx, cfg)

	if (result && typeof result === 'object' && 'revalidate' in result) {
		const { revalidate, ...rest } = result as any
		return rest
	}

	return result
}
