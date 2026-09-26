import { ArrowUpRight, MapPin, MessageCircle, Stethoscope } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Reveal } from '@/components/shared/Reveal'

export function Contact() {
  return (
    <section id="kontak" className="relative overflow-hidden py-24 sm:py-32">
      <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <Reveal>
          <div className="relative overflow-hidden rounded-[2.5rem] bg-forest-deep px-6 py-16 text-center text-white shadow-2xl shadow-forest/30 sm:px-16">
            <div className="bg-grid-dark absolute inset-0 [mask-image:radial-gradient(60%_60%_at_50%_50%,black,transparent)]" />
            <div className="animate-blob absolute -top-24 left-1/4 size-72 rounded-full bg-primary/30 blur-3xl" />
            <div className="animate-blob absolute -right-16 -bottom-24 size-72 rounded-full bg-gold/20 blur-3xl [animation-delay:-8s]" />

            <div className="relative">
              <img
                src="/image/RSB.png"
                alt="Logo Rumah Sakit Bhayangkara Bogor"
                className="animate-float mx-auto size-24 rounded-full bg-white p-1.5 shadow-xl shadow-black/30"
              />
              <h2 className="mt-8 text-3xl font-extrabold tracking-tight text-balance sm:text-4xl lg:text-5xl">
                Siap menjangkau,{' '}
                <span className="text-gradient-gold">siap melayani</span>
              </h2>
              <p className="mx-auto mt-4 max-w-2xl text-base leading-relaxed text-white/70">
                PNPP Polresta Bogor Kota dapat menerima informasi layanan, jadwal praktik dokter,
                dan pengingat kontrol berkala langsung melalui kanal digital resmi PROAKTIF.
                Kunjungi kami atau sapa tim PROAKTIF melalui WhatsApp Business resmi rumah sakit.
              </p>

              <div className="mt-10 flex flex-wrap items-center justify-center gap-4">
                <Button
                  asChild
                  size="lg"
                  className="h-12 rounded-full bg-gold px-7 text-base font-bold text-forest-ink shadow-xl shadow-gold/25 transition-transform hover:scale-[1.03] hover:bg-gold/90"
                >
                  <a href="https://wa.me/6285924361866" target="_blank" rel="noopener noreferrer">
                    <MessageCircle />
                    WhatsApp Business
                    <ArrowUpRight />
                  </a>
                </Button>
                <Button
                  asChild
                  size="lg"
                  variant="outline"
                  className="h-12 rounded-full border-white/25 bg-white/5 px-7 text-base font-semibold text-white backdrop-blur hover:bg-white/15 hover:text-white"
                >
                  <a href="#tentang">
                    <Stethoscope />
                    Pelajari PROAKTIF
                  </a>
                </Button>
              </div>

              <div className="mt-10 inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-5 py-2.5 text-sm text-white/70 backdrop-blur">
                <MapPin className="size-4 text-gold" />
                Jl. Kapten Muslihat No. 18, Bogor Tengah, Kota Bogor
              </div>
            </div>
          </div>
        </Reveal>
      </div>
    </section>
  )
}
