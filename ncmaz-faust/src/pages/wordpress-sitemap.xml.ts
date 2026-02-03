import { GetStaticProps } from 'next'

// Sitemap component
export default function WPSitemap() {}

export const getStaticProps: GetStaticProps = async () => {
	return { notFound: true }
}
