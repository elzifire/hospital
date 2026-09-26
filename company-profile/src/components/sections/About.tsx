import { HeartPulse } from 'lucide-react'
import { Card, CardContent } from '@/components/ui/card'
import { Reveal } from '@/components/shared/Reveal'
import { SectionHeading } from '@/components/shared/SectionHeading'
import { pillars } from '@/data/content'

export function About() {
  return (
    <section id="tentang" className="relative overflow-hidden py-24 sm:py-32">
      <div className="bg-grid-light absolute inset-0 -z-10 [mask-image:radial-gradient(70%_60%_at_50%_40%,black,transparent)]" />
      <div className="absolute top-0 left-1/2 -z-10 h-72 w-[42rem] -translate-x-1/2 rounded-full bg-primary/10 blur-3xl" />

      <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <SectionHeading
          eyebrow="Tentang Inovasi"
          title="Satu sistem, lima pilar terintegrasi"
          description="PROAKTIF merupakan inovasi pelayanan yang mengintegrasikan komunikasi digital, database PNPP, sistem pengingat, media edukasi kesehatan, serta dashboard monitoring dalam satu sistem yang saling terhubung."
        />

        <div className="mt-16 grid gap-10 lg:grid-cols-[1.1fr_1fr] lg:items-center">
          <Reveal>
            <div className="space-y-5 text-base leading-relaxed text-muted-foreground">
              <p>
                Melalui inovasi ini,{' '}
                <span className="font-semibold text-forest-ink">RS Bhayangkara Bogor</span> tidak
                lagi hanya menunggu pasien datang (<em>passive service</em>), tetapi secara
                proaktif menjangkau PNPP dengan memberikan informasi layanan kesehatan, jadwal
                praktik dokter, kontrol berkala, dan program kesehatan lainnya melalui media
                digital.
              </p>
              <p>
                Implementasi PROAKTIF diharapkan mampu meningkatkan efektivitas komunikasi antara
                rumah sakit dengan PNPP, memperluas akses informasi pelayanan kesehatan,
                meningkatkan kepatuhan terhadap pemeriksaan kesehatan berkala, serta mendorong
                peningkatan kunjungan pasien sehingga berdampak pada optimalisasi pemanfaatan
                layanan dan peningkatan pendapatan rumah sakit.
              </p>
              <div className="flex items-center gap-3 rounded-2xl border border-gold/40 bg-gold/10 px-5 py-4">
                <HeartPulse className="size-6 shrink-0 text-primary" />
                <p className="text-sm font-semibold text-forest-ink italic sm:text-base">
                  “Sehat Bersama, Melayani dengan Hati”
                </p>
              </div>
            </div>
          </Reveal>

          <Reveal delay={0.15}>
            <div className="relative mx-auto max-w-sm">
              <div className="absolute -inset-3 -z-10 rounded-[2rem] bg-gradient-to-br from-primary/25 via-transparent to-gold/30 blur-xl" />
              <div className="overflow-hidden rounded-[1.75rem] border border-forest/10 bg-white shadow-2xl shadow-forest/15">
                <img
                  src="/image/logo-proaktif.jpeg"
                  alt="Logo PROAKTIF — Program Outreach Kesehatan Terintegrasi untuk PNPP"
                  className="aspect-square w-full object-cover transition-transform duration-700 hover:scale-105"
                />
              </div>
              <div className="absolute -right-4 -bottom-5 rounded-2xl border border-forest/10 bg-white px-4 py-3 shadow-xl shadow-forest/10">
                <p className="text-xs font-bold tracking-widest text-primary uppercase">
                  Est. Off Campus
                </p>
                <p className="text-lg font-extrabold text-forest-ink">60 Hari Kerja</p>
              </div>
            </div>
          </Reveal>
        </div>

        <div className="mt-20 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          {pillars.map((pillar, index) => (
            <Reveal key={pillar.title} delay={index * 0.08}>
              <Card className="group h-full border-0 bg-white/80 shadow-sm ring-forest/10 backdrop-blur transition-all duration-300 hover:-translate-y-1.5 hover:shadow-xl hover:shadow-primary/10 hover:ring-primary/30">
                <CardContent className="flex flex-col gap-3 p-6">
                  <span className="flex size-12 items-center justify-center rounded-2xl bg-gradient-to-br from-primary to-forest text-white shadow-lg shadow-primary/25 transition-transform duration-300 group-hover:scale-110 group-hover:rotate-3">
                    <pillar.icon className="size-6" />
                  </span>
                  <h3 className="text-lg font-bold text-forest-ink">{pillar.title}</h3>
                  <p className="text-sm leading-relaxed text-muted-foreground">
                    {pillar.description}
                  </p>
                </CardContent>
              </Card>
            </Reveal>
          ))}
          <Reveal delay={pillars.length * 0.08}>
            <div className="flex h-full flex-col justify-between rounded-xl bg-gradient-to-br from-forest-deep to-forest p-6 text-white shadow-xl shadow-forest/25">
              <p className="text-sm leading-relaxed text-white/80">
                Kelima pilar bekerja saling terhubung membentuk ekosistem outreach digital yang
                utuh — dari data hingga evaluasi.
              </p>
              <p className="mt-4 text-lg font-extrabold text-gold">1 Ekosistem PROAKTIF</p>
            </div>
          </Reveal>
        </div>
      </div>
    </section>
  )
}
