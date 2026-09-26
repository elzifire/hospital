import { Card, CardContent } from '@/components/ui/card'
import { Reveal } from '@/components/shared/Reveal'
import { SectionHeading } from '@/components/shared/SectionHeading'
import { outputs } from '@/data/content'

export function Deliverables() {
  return (
    <section id="output" className="relative overflow-hidden py-24 sm:py-32">
      <div className="absolute top-1/3 -right-32 -z-10 size-96 rounded-full bg-gold/15 blur-3xl" />

      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <SectionHeading
          eyebrow="Output Inovasi"
          title="10 keluaran selama 60 hari Off Campus"
          description="Seluruh output dirancang agar PROAKTIF tidak berhenti sebagai proyek, melainkan menjadi layanan berkelanjutan yang dilegalkan dan dievaluasi."
        />

        <div className="mt-16 grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
          {outputs.map((output, index) => (
            <Reveal key={output.title} delay={(index % 5) * 0.07}>
              <Card className="group relative h-full border-0 bg-white shadow-sm ring-forest/10 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl hover:shadow-gold/15 hover:ring-gold/40">
                <CardContent className="flex h-full flex-col gap-3 p-6">
                  <div className="flex items-center justify-between">
                    <span className="flex size-11 items-center justify-center rounded-xl bg-gradient-to-br from-gold to-gold-soft text-forest-ink shadow-md shadow-gold/25 transition-transform duration-300 group-hover:scale-110 group-hover:-rotate-6">
                      <output.icon className="size-5" />
                    </span>
                    <span className="text-3xl font-black text-forest/10 transition-colors duration-300 group-hover:text-gold/40">
                      {String(index + 1).padStart(2, '0')}
                    </span>
                  </div>
                  <h3 className="text-base leading-snug font-bold text-forest-ink">
                    {output.title}
                  </h3>
                  <p className="text-xs leading-relaxed text-muted-foreground">
                    {output.description}
                  </p>
                </CardContent>
              </Card>
            </Reveal>
          ))}
        </div>

        <Reveal delay={0.2} className="mt-14">
          <div className="relative overflow-hidden rounded-3xl border border-forest/10 bg-white p-8 shadow-lg shadow-forest/5 sm:p-10">
            <div className="absolute inset-y-0 left-0 w-1.5 bg-gradient-to-b from-primary via-gold to-primary" />
            <div className="grid gap-8 lg:grid-cols-[auto_1fr] lg:items-center">
              <img
                src="/image/ATAP.png"
                alt="Gedung Instalasi Gawat Darurat RS Bhayangkara Bogor"
                className="h-44 w-full rounded-2xl object-cover shadow-md lg:w-80"
              />
              <div>
                <h3 className="text-xl font-bold text-forest-ink sm:text-2xl">
                  Berakar di rumah sakit, tumbuh untuk PNPP
                </h3>
                <p className="mt-3 text-sm leading-relaxed text-muted-foreground sm:text-base">
                  Seluruh output PROAKTIF disusun bersama jajaran RS Bhayangkara Bogor dan
                  dilegalkan melalui Keputusan Karumkit, sehingga inovasi ini menjadi bagian dari
                  sistem pelayanan rumah sakit — bukan sekadar kegiatan sementara.
                </p>
              </div>
            </div>
          </div>
        </Reveal>
      </div>
    </section>
  )
}
