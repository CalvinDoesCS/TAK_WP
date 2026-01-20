import '@/styles/globals.css'

export const metadata = {
  title: 'DaaS Platform | WebAssembly Powered',
  description: 'Next-generation virtual desktop infrastructure',
}

export default function RootLayout({
  children,
}: {
  children: React.ReactNode
}) {
  return (
    <html lang="en">
      <body>{children}</body>
    </html>
  )
}
