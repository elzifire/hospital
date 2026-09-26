import { Reveal } from '@/components/shared/Reveal'
import { SectionHeading } from '@/components/shared/SectionHeading'
import { steps } from '@/data/content'
import { cn } from 'cn'

export function HowItWorks() {
  return (
    <section id="alur" className="relative overflow-hidden bg-muted/60 py-24 sm:py-32">
      <div className="bg-grid-light absolute inset-0 -z-10 [mask-image:radial-gradient(60%_60%_at_50%_50%,black,transparent)]" />

      <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <SectionHeading
          eyebrow="Tata Cara Pelaksanaan"
          title="Enam tahapan alur kerja PROAKTIF"
          description="Siklus berkelanjutan dari registrasi data hingga monitoring dan evaluasi berbasis data."
        />

        <div className="relative mt-20">
          <div className="absolute top-0 bottom-0 left-5 w-px bg-gradient-to-b from-primary/60 via-gold/60 to-primary/60 md:left-1/2 md:-translate-x-1/2" />

          <ol className="space-y-12 md:space-y-16">
            {steps.map((step, index) => {
              const isEven = index % 2 === 0
              return (
                <li key={step.title} className="relative">
                  <Reveal
                    delay={0.05}
                    className={cn(
                      'relative pl-16 md:w-1/2 md:pl-0',
                      isEven ? 'md:pr-14 md:text-right' : 'md:ml-auto md:pl-14',
                    )}
                  >
                    <span
                      className={cn(
                        'absolute top-0 left-0 flex size-11 items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-forest text-sm font-black text-white shadow-lg shadow-primary/30 ring-4 ring-background md:top-2',
                        isEven
                          ? 'md:left-auto md:-right-5.5'
                          : 'md:-left-5.5',
                      )}
                    >
                      {String(index + 1).padStart(2, '0')}
                    </span>

                    <div className="group rounded-3xl border border-forest/10 bg-white p-7 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-primary/30 hover:shadow-xl hover:shadow-primary/10">
                      <div
                        className={cn(
                          'flex items-center gap-4',
                          isEven && 'md:flex-row-reverse',
                        )}
                      >
                        <span className="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-gold/15 text-forest transition-transform duration-300 group-hover:scale-110">
                          <step.icon className="size-6" />
                        </span>
                        <h3 className="text-lg font-bold text-forest-ink sm:text-xl">
                          {step.title}
                        </h3>
                      </div>
                      <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
                        {step.description}
                      </p>
                    </div>
                  </Reveal>
                </li>
              )
            })}
          </ol>
        </div>
      </div>
    </section>
  )
}
