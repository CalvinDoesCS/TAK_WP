import { getWordPressProps, WordPressTemplate } from '@faustwp/core'
import { WordPressTemplateProps } from '../types'
import { GetStaticProps } from 'next'
import { REVALIDATE_OPTIONS } from '@/contains/contants'

export default function Page(props: WordPressTemplateProps) {
	return <WordPressTemplate {...props} />
}

export const getStaticProps: GetStaticProps = async (ctx) => {
	const result = await getWordPressProps({ ctx, ...REVALIDATE_OPTIONS })

	if (result && typeof result === 'object' && 'revalidate' in result) {
		const { revalidate, ...rest } = result as any
		return rest
	}

	return result
}
