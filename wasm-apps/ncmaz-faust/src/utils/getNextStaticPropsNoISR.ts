import { getNextStaticProps } from '@faustwp/core'
import type { GetStaticPropsContext } from 'next'

export async function getNextStaticPropsNoISR(
    ctx: GetStaticPropsContext,
    cfg: any,
) {
    // Return empty shell - no WP data fetching during build
    // Data will be injected at runtime by the WASM loader
    return {
        props: {},
    }
    
    // Original implementation (commented out for static shell builds):
    // const result = await getNextStaticProps(ctx, cfg)
    // if (result && typeof result === 'object' && 'revalidate' in result) {
    // 	const { revalidate, ...rest } = result as any
    // 	return rest
    // }
    // return result
}
