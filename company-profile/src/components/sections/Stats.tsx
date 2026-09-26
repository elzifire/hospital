import { Counter } from '@/components/shared/Counter'
import { Reveal } from '@/components/shared/Reveal'
import { stats } from '@/data/content'

export function Stats() {
  return (
    <section className="relative overflow-hidden bg-gradient-to-br from-forest-deep via-forest to-forest-deep py-20 text-white">
      <div className="bg-grid-dark absolute inset-0 [mask-image:radial-gradient(70%_70%_at_50%_50%,black,transparent)]" />
      <svg
        viewBox="0 0 1200 120"
        preserveAspectRatio="none"
        className="absolute inset-x-0 top-1/2 h-24 w-full -translate-y-1/2 opacity-25"
        aria-hidden="true"
      >
        <path
          d="M0 60 H300 l20 -34 24 68 22 -50 18 32 16 -16 H700 l22 -40 26 76 20 -52 16 30 14 -14 H1200"
          fill="none"
          stroke="var(--gold)"
          strokeWidth="2.5"
          strokeDasharray="1200"
          className="animate-ecg"
        />
      </svg>

      <div className="relative mx-auto grid max-w-6xl grid-cols-2 gap-10 px-4 sm:px-6 lg:grid-cols-4 lg:px-8">
        {stats.map((stat, index) => (
          <Reveal key={stat.label} delay={index * 0.1} className="text-center">
            <p className="text-5xl font-black tracking-tight text-gold sm:text-6xl">
              <Counter to={stat.value} suffix={stat.suffix} />
            </p>
            <p className="mt-2 text-sm font-bold tracking-widest uppercase">{stat.label}</p>
            <p className="mt-1 text-xs text-white/60">{stat.detail}</p>
          </Reveal>
        ))}
      </div>
    </section>
  )
}
