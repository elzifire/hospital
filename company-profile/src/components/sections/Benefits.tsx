import { Card, CardContent } from '@/components/ui/card'
import { Reveal } from '@/components/shared/Reveal'
import { SectionHeading } from '@/components/shared/SectionHeading'
import { benefits } from '@/data/content'

export function Benefits() {
  return (
    <section id="manfaat" className="relative overflow-hidden py-24 sm:py-32">
      <div className="absolute -top-24 right-0 -z-10 size-96 rounded-full bg-gold/15 blur-3xl" />
      <div className="absolute bottom-0 -left-24 -z-10 size-96 rounded-full bg-primary/10 blur-3xl" />

      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <SectionHeading
          eyebrow="Manfaat"
          title="Dampak nyata bagi PNPP & rumah sakit"
          description="Implementasi PROAKTIF dirancang untuk memberi manfaat berlapis: bagi pegawai, bagi pelayanan, dan bagi institusi."
        />

        <div className="mt-16 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {benefits.map((benefit, index) => (
            <Reveal key={benefit.title} delay={index * 0.07}>
              <Card className="group relative h-full overflow-hidden border-0 bg-white shadow-sm ring-forest/10 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl hover:shadow-primary/15 hover:ring-primary/25">
                <div className="absolute inset-x-0 top-0 h-1 origin-left scale-x-0 bg-gradient-to-r from-primary to-gold transition-transform duration-500 group-hover:scale-x-100" />
                <CardContent className="flex flex-col gap-4 p-7">
                  <div className="flex items-center justify-between">
                    <span className="flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary transition-all duration-300 group-hover:bg-primary group-hover:text-white group-hover:shadow-lg group-hover:shadow-primary/30">
                      <benefit.icon className="size-6" />
                    </span>
                    <span className="text-4xl font-black text-forest/8 select-none">
                      {String(index + 1).padStart(2, '0')}
                    </span>
                  </div>
                  <h3 className="text-lg font-bold text-forest-ink">{benefit.title}</h3>
                  <p className="text-sm leading-relaxed text-muted-foreground">
                    {benefit.description}
                  </p>
                </CardContent>
              </Card>
            </Reveal>
          ))}
        </div>
      </div>
    </section>
  )
}
