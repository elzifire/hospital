import { ArrowRight, Check, Hourglass, Rocket, X } from 'lucide-react'
import { Reveal } from '@/components/shared/Reveal'
import { SectionHeading } from '@/components/shared/SectionHeading'
import { comparison } from '@/data/content'

export function Comparison() {
  return (
    <section className="relative overflow-hidden bg-forest-deep py-24 text-white sm:py-32">
      <div className="bg-grid-dark absolute inset-0 [mask-image:radial-gradient(60%_60%_at_50%_50%,black,transparent)]" />
      <div className="animate-blob absolute -bottom-32 left-1/4 size-96 rounded-full bg-primary/25 blur-3xl" />

      <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <SectionHeading
          dark
          eyebrow="Transformasi Layanan"
          title="Dari menunggu, menjadi menjangkau"
          description="Perubahan paradigma pelayanan rumah sakit terhadap PNPP Polresta Bogor Kota."
        />

        <div className="mt-16 grid items-stretch gap-6 lg:grid-cols-[1fr_auto_1fr]">
          <Reveal>
            <div className="h-full rounded-3xl border border-white/10 bg-white/5 p-8 backdrop-blur">
              <div className="flex items-center gap-4">
                <span className="flex size-12 items-center justify-center rounded-2xl bg-white/10">
                  <Hourglass className="size-6 text-white/60" />
                </span>
                <div>
                  <p className="text-xs font-semibold tracking-widest text-white/50 uppercase">
                    {comparison.before.subtitle}
                  </p>
                  <h3 className="text-xl font-bold text-white/80">{comparison.before.title}</h3>
                </div>
              </div>
              <ul className="mt-6 space-y-3.5">
                {comparison.before.points.map((point) => (
                  <li key={point} className="flex items-start gap-3 text-sm text-white/60">
                    <X className="mt-0.5 size-4 shrink-0 text-red-300/80" />
                    {point}
                  </li>
                ))}
              </ul>
            </div>
          </Reveal>

          <Reveal delay={0.1} className="hidden lg:flex">
            <div className="flex h-full items-center">
              <span className="animate-pulse-soft flex size-14 items-center justify-center rounded-full bg-gold text-forest-ink shadow-xl shadow-gold/30">
                <ArrowRight className="size-6" />
              </span>
            </div>
          </Reveal>

          <Reveal delay={0.15}>
            <div className="relative h-full overflow-hidden rounded-3xl border border-gold/30 bg-gradient-to-br from-primary/30 to-forest/40 p-8 shadow-2xl shadow-black/30 backdrop-blur">
              <div className="absolute -top-16 -right-16 size-48 rounded-full bg-gold/20 blur-3xl" />
              <div className="flex items-center gap-4">
                <span className="flex size-12 items-center justify-center rounded-2xl bg-gold text-forest-ink shadow-lg shadow-gold/30">
                  <Rocket className="size-6" />
                </span>
                <div>
                  <p className="text-xs font-semibold tracking-widest text-gold uppercase">
                    {comparison.after.subtitle}
                  </p>
                  <h3 className="text-xl font-bold text-white">{comparison.after.title}</h3>
                </div>
              </div>
              <ul className="mt-6 space-y-3.5">
                {comparison.after.points.map((point) => (
                  <li key={point} className="flex items-start gap-3 text-sm text-white/90">
                    <span className="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full bg-gold/90">
                      <Check className="size-3 text-forest-ink" />
                    </span>
                    {point}
                  </li>
                ))}
              </ul>
            </div>
          </Reveal>
        </div>
      </div>
    </section>
  )
}
