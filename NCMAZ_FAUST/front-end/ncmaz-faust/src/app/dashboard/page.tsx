'use client'

import { useState } from 'react'
import { MicroAppContainer } from '@/components/MicroAppContainer'
import { MICRO_APPS } from '@/lib/microAppRegistry'

const apps = [
  { id: 'octarine', route: '/admin', bg: 'from-blue-600 to-indigo-600' },
  { id: 'opencore', route: '/app', bg: 'from-emerald-600 to-teal-600' },
  { id: 'wplms', route: '/courses', bg: 'from-purple-600 to-pink-600' }
]

export default function Dashboard() {
  const [active, setActive] = useState('octarine')
  const app = MICRO_APPS[active]
  const grad = apps.find(a => a.id === active)?.bg

  return (
    <main className="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 p-8">
      <div className="max-w-7xl mx-auto">
        <div className="text-center mb-10">
          <div className="inline-block px-5 py-2 mb-3 text-sm font-semibold text-blue-700 bg-blue-100 rounded-full animate-pulse">
            ⚡ WebAssembly Powered
          </div>
          <h1 className={`text-6xl font-bold bg-gradient-to-r ${grad} bg-clip-text text-transparent mb-3`}>
            DaaS Platform
          </h1>
          <p className="text-lg text-gray-600">Virtual Desktop Infrastructure</p>
        </div>

        <div className="grid grid-cols-3 gap-5 mb-10">
          {apps.map(a => {
            const info = MICRO_APPS[a.id]
            const sel = active === a.id
            return (
              <button
                key={a.id}
                onClick={() => setActive(a.id)}
                className={`p-7 rounded-2xl border-2 transition-all duration-300 transform hover:scale-105 ${
                  sel ? `bg-gradient-to-br ${a.bg} border-transparent shadow-2xl scale-105` : 'bg-white border-gray-200 hover:shadow-xl'
                }`}
              >
                <div className="text-5xl mb-3">{info?.icon}</div>
                <h3 className={`text-xl font-bold mb-2 ${sel ? 'text-white' : 'text-gray-900'}`}>{info?.name}</h3>
                <p className={`text-sm ${sel ? 'text-white/90' : 'text-gray-600'}`}>{info?.description}</p>
                {sel && <div className="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-white/20 rounded-full text-white text-sm font-semibold">
                  <span className="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>Active
                </div>}
              </button>
            )
          })}
        </div>

        <section className="bg-white/70 backdrop-blur-xl border border-white/20 rounded-3xl shadow-2xl overflow-hidden">
          <div className={`px-7 py-5 bg-gradient-to-r ${grad} flex items-center justify-between`}>
            <div className="flex items-center gap-3">
              <div className="text-4xl">{app?.icon}</div>
              <div>
                <h2 className="text-2xl font-bold text-white">{app?.name}</h2>
                <p className="text-white/80 text-sm">{app?.description}</p>
              </div>
            </div>
            <div className="flex items-center gap-2 px-4 py-2 bg-green-500/20 rounded-full">
              <span className="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
              <span className="text-sm font-semibold text-white">Live</span>
            </div>
          </div>
          
          <div className="p-7">
            <MicroAppContainer appId={active} route={apps.find(a => a.id === active)?.route || '/'} />
          </div>
        </section>

        <div className="mt-10 text-center">
          <div className="inline-flex items-center gap-3 px-5 py-3 bg-white/80 backdrop-blur-md rounded-full shadow-lg">
            <div className="flex -space-x-2">
              <div className="w-7 h-7 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-full border-2 border-white"></div>
              <div className="w-7 h-7 bg-gradient-to-br from-emerald-500 to-teal-500 rounded-full border-2 border-white"></div>
              <div className="w-7 h-7 bg-gradient-to-br from-purple-500 to-pink-500 rounded-full border-2 border-white"></div>
            </div>
            <p className="text-sm font-medium text-gray-700">3 apps at <span className="text-blue-600 font-bold">near-native speed</span></p>
          </div>
        </div>
      </div>
    </main>
  )
}
