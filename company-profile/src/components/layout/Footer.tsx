import { HeartPulse, MapPin } from 'lucide-react'
import { navLinks } from '@/data/content'

export function Footer() {
  return (
    <footer className="relative overflow-hidden bg-forest-ink pt-16 pb-8 text-white">
      <div className="bg-grid-dark absolute inset-0 [mask-image:linear-gradient(to_bottom,black,transparent_60%)]" />

      <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div className="grid gap-12 lg:grid-cols-[1.4fr_1fr_1fr]">
          <div>
            <div className="flex items-center gap-3">
              <img
                src="/image/logo-proaktif.jpeg"
                alt="Logo PROAKTIF"
                className="size-12 rounded-xl object-cover ring-2 ring-gold/60"
              />
              <div>
                <p className="text-lg font-extrabold tracking-tight">PROAKTIF</p>
                <p className="text-xs font-semibold tracking-[0.18em] text-gold uppercase">
                  RS Bhayangkara Bogor
                </p>
              </div>
            </div>
            <p className="mt-5 max-w-md text-sm leading-relaxed text-white/60">
              Program Outreach Kesehatan Terintegrasi untuk PNPP — mengintegrasikan komunikasi
              digital, database PNPP, sistem pengingat, media edukasi kesehatan, dan dashboard
              monitoring dalam satu sistem yang saling terhubung.
            </p>
            <p className="mt-5 flex items-center gap-2 text-sm font-semibold text-gold italic">
              <HeartPulse className="size-4" />
              Sehat Bersama, Melayani dengan Hati
            </p>
          </div>

          <div>
            <p className="text-sm font-bold tracking-widest text-white/80 uppercase">Navigasi</p>
            <ul className="mt-5 space-y-3">
              {navLinks.map((link) => (
                <li key={link.href}>
                  <a
                    href={link.href}
                    className="text-sm text-white/60 transition-colors hover:text-gold"
                  >
                    {link.label}
                  </a>
                </li>
              ))}
            </ul>
          </div>

          <div>
            <p className="text-sm font-bold tracking-widest text-white/80 uppercase">Kontak</p>
            <ul className="mt-5 space-y-4 text-sm text-white/60">
              <li className="flex items-start gap-3">
                <MapPin className="mt-0.5 size-4 shrink-0 text-gold" />
                Jl. Kapten Muslihat No. 18, Bogor Tengah, Kota Bogor, Jawa Barat
              </li>
              <li className="flex items-start gap-3">
                <img src="/image/RSB.png" alt="" className="mt-0.5 size-8 rounded-full bg-white/90 p-0.5" />
                Rumah Sakit Bhayangkara TK. III Bogor
              </li>
            </ul>
          </div>
        </div>

        <div className="mt-14 flex flex-col items-center justify-between gap-4 border-t border-white/10 pt-6 sm:flex-row">
          <p className="text-xs text-white/50">
            © {new Date().getFullYear()} PROAKTIF — RS Bhayangkara Bogor. Inovasi pelayanan Off
            Campus.
          </p>
          <p className="text-xs text-white/50">Dibangun untuk PNPP Polresta Bogor Kota</p>
        </div>
      </div>
    </footer>
  )
}
