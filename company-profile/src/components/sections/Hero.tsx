import { ArrowDown, BadgeCheck, BellRing, CheckCheck, Send } from 'lucide-react'
import { motion } from 'motion/react'
import { Button } from '@/components/ui/button'
import { marqueeItems } from '@/data/content'

const chatMessages = [
  {
    from: 'rs' as const,
    text: 'Selamat pagi, Bapak/Ibu PNPP Polresta Bogor 👋',
    time: '07.30',
  },
  {
    from: 'rs' as const,
    text: 'Ingat! Jadwal kontrol berkala Anda: Jumat, pukul 09.00 WIB di Poliklinik Umum.',
    time: '07.30',
  },
  {
    from: 'user' as const,
    text: 'Baik, saya konfirmasi hadir. Terima kasih 🙏',
    time: '07.32',
  },
]

const container = {
  hidden: {},
  show: { transition: { staggerChildren: 0.12, delayChildren: 0.2 } },
}

const item = {
  hidden: { opacity: 0, y: 28 },
  show: {
    opacity: 1,
    y: 0,
    transition: { duration: 0.7, ease: [0.21, 0.47, 0.32, 0.98] as const },
  },
}

export function Hero() {
  return (
    <section
      id="beranda"
      className="relative isolate flex min-h-svh flex-col overflow-hidden bg-forest-deep pt-20 text-white"
    >
      <div className="bg-grid-dark absolute inset-0 -z-10" />
      <div className="absolute inset-0 -z-10 bg-[radial-gradient(80%_60%_at_50%_0%,oklch(0.38_0.09_155/0.55),transparent_70%)]" />
      <div className="animate-blob absolute -top-24 -left-24 -z-10 size-96 rounded-full bg-primary/30 blur-3xl" />
      <div className="animate-blob absolute top-1/3 -right-32 -z-10 size-[28rem] rounded-full bg-gold/20 blur-3xl [animation-delay:-6s]" />
      <div className="absolute inset-x-0 bottom-0 -z-10 h-40 bg-gradient-to-t from-forest-deep to-transparent" />

      <div className="mx-auto grid w-full max-w-7xl flex-1 items-center gap-16 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:px-8">
        <motion.div variants={container} initial="hidden" animate="show">
          <motion.div variants={item} className="inline-flex items-center gap-2">
            <span className="glass inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-semibold tracking-wide text-gold">
              <span className="animate-pulse-soft size-2 rounded-full bg-gold" />
              Inovasi Pelayanan RS Bhayangkara Bogor
            </span>
          </motion.div>

          <motion.h1
            variants={item}
            className="mt-6 text-5xl font-black tracking-tight text-balance sm:text-6xl lg:text-7xl"
          >
            PRO<span className="text-gradient-gold">AKTIF</span>
          </motion.h1>

          <motion.p
            variants={item}
            className="mt-3 text-lg font-bold tracking-wide text-white/90 uppercase sm:text-xl"
          >
            Program Outreach Kesehatan Terintegrasi untuk{' '}
            <span className="text-gold">PNPP</span>
          </motion.p>

          <motion.p variants={item} className="mt-5 max-w-xl text-base leading-relaxed text-white/70">
            Mengintegrasikan komunikasi digital, database PNPP, sistem pengingat, media edukasi
            kesehatan, dan dashboard monitoring dalam satu ekosistem. Kami tidak lagi menunggu
            pasien datang — kami menjangkau Anda lebih dulu.
          </motion.p>

          <motion.div variants={item} className="mt-8 flex flex-wrap items-center gap-4">
            <Button
              asChild
              size="lg"
              className="h-12 rounded-full bg-gold px-7 text-base font-bold text-forest-ink shadow-xl shadow-gold/25 transition-transform hover:scale-[1.03] hover:bg-gold/90"
            >
              <a href="#alur">
                Jelajahi Cara Kerja
                <ArrowDown />
              </a>
            </Button>
            <Button
              asChild
              size="lg"
              variant="outline"
              className="h-12 rounded-full border-white/25 bg-white/5 px-7 text-base font-semibold text-white backdrop-blur hover:bg-white/15 hover:text-white"
            >
              <a href="#output">Lihat Output Inovasi</a>
            </Button>
          </motion.div>

          <motion.dl
            variants={item}
            className="mt-10 grid max-w-lg grid-cols-3 divide-x divide-white/10 rounded-2xl border border-white/10 bg-white/5 py-4 backdrop-blur"
          >
            {[
              { value: '60 Hari', label: 'Off Campus' },
              { value: '6 Tahap', label: 'Pelaksanaan' },
              { value: '10 Output', label: 'Inovasi' },
            ].map((stat) => (
              <div key={stat.label} className="px-4 text-center">
                <dt className="order-2 text-xs font-medium tracking-wide text-white/60 uppercase">
                  {stat.label}
                </dt>
                <dd className="text-xl font-extrabold text-gold">{stat.value}</dd>
              </div>
            ))}
          </motion.dl>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, y: 60 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.9, delay: 0.4, ease: [0.21, 0.47, 0.32, 0.98] }}
          className="relative mx-auto w-full max-w-sm lg:max-w-md"
        >
          <div className="animate-float-slow absolute -top-10 -left-6 z-10 hidden sm:block">
            <div className="glass flex items-center gap-3 rounded-2xl px-4 py-3 shadow-2xl shadow-black/30">
              <img
                src="/image/logo-proaktif.jpeg"
                alt="Logo PROAKTIF"
                className="size-12 rounded-xl object-cover ring-2 ring-gold/70"
              />
              <div>
                <p className="text-sm font-bold text-white">Tim PROAKTIF</p>
                <p className="text-xs text-white/60">Siap melayani PNPP</p>
              </div>
            </div>
          </div>

          <div className="animate-float relative mx-auto w-[300px] rounded-[2.75rem] border border-white/15 bg-white/10 p-3 shadow-2xl shadow-black/40 backdrop-blur-xl sm:w-[320px]">
            <div className="overflow-hidden rounded-[2.25rem] bg-[#0c1f16]">
              <div className="flex items-center gap-3 border-b border-white/10 bg-primary/25 px-4 py-3">
                <img
                  src="/image/logo-proaktif.jpeg"
                  alt=""
                  className="size-9 rounded-full object-cover ring-2 ring-gold/60"
                />
                <div className="min-w-0">
                  <p className="truncate text-sm font-bold text-white">PROAKTIF</p>
                  <p className="flex items-center gap-1 text-[0.65rem] text-emerald-300">
                    <span className="size-1.5 rounded-full bg-emerald-400" />
                    RS Bhayangkara Bogor · online
                  </p>
                </div>
                <BellRing className="ml-auto size-4 text-gold" />
              </div>

              <div className="space-y-3 px-3 py-4">
                {chatMessages.map((message, index) => (
                  <motion.div
                    key={message.text}
                    initial={{ opacity: 0, y: 16, scale: 0.96 }}
                    animate={{ opacity: 1, y: 0, scale: 1 }}
                    transition={{ delay: 1 + index * 0.55, duration: 0.45 }}
                    className={
                      message.from === 'user'
                        ? 'ml-8 rounded-2xl rounded-br-sm bg-primary/80 px-3 py-2 text-xs leading-relaxed text-white shadow'
                        : 'mr-8 rounded-2xl rounded-bl-sm bg-white/10 px-3 py-2 text-xs leading-relaxed text-white/90 shadow'
                    }
                  >
                    {message.text}
                    <span className="mt-1 flex items-center justify-end gap-1 text-[0.6rem] text-white/50">
                      {message.time}
                      {message.from === 'user' ? <CheckCheck className="size-3 text-sky-300" /> : null}
                    </span>
                  </motion.div>
                ))}

                <motion.div
                  initial={{ opacity: 0, y: 16 }}
                  animate={{ opacity: 1, y: 0 }}
                  transition={{ delay: 2.8, duration: 0.45 }}
                  className="mr-8 rounded-2xl rounded-bl-sm border border-gold/30 bg-gold/15 px-3 py-2 shadow"
                >
                  <p className="text-[0.65rem] font-bold tracking-wide text-gold uppercase">
                    Jadwal praktik dokter
                  </p>
                  <p className="mt-0.5 text-xs leading-relaxed text-white/85">
                    Info minggu ini telah diperbarui. Poliklinik Umum & Gigi siap melayani.
                  </p>
                </motion.div>

                <motion.div
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  transition={{ delay: 3.4, duration: 0.5 }}
                  className="flex items-center gap-2 pl-2"
                >
                  <span className="flex gap-1 rounded-full bg-white/10 px-3 py-2">
                    <span className="animate-pulse-soft size-1.5 rounded-full bg-white/60" />
                    <span className="animate-pulse-soft size-1.5 rounded-full bg-white/60 [animation-delay:0.3s]" />
                    <span className="animate-pulse-soft size-1.5 rounded-full bg-white/60 [animation-delay:0.6s]" />
                  </span>
                  <Send className="size-4 text-white/40" />
                </motion.div>
              </div>
            </div>
          </div>

          <motion.div
            initial={{ opacity: 0, x: 24 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ delay: 1.6, duration: 0.6 }}
            className="animate-float absolute top-1/4 -right-4 z-10 hidden sm:block [animation-delay:-3s]"
          >
            <div className="glass flex items-center gap-2 rounded-2xl px-4 py-3 shadow-2xl shadow-black/30">
              <span className="flex size-8 items-center justify-center rounded-full bg-emerald-400/20">
                <BadgeCheck className="size-4 text-emerald-300" />
              </span>
              <div>
                <p className="text-xs font-bold text-white">Reminder terkirim</p>
                <p className="text-[0.65rem] text-white/60">1.248 PNPP terjangkau</p>
              </div>
            </div>
          </motion.div>

          <motion.div
            initial={{ opacity: 0, x: -24 }}
            animate={{ opacity: 1, x: 0 }}
            transition={{ delay: 2.2, duration: 0.6 }}
            className="animate-float-slow absolute -bottom-8 -left-8 z-10 hidden sm:block [animation-delay:-5s]"
          >
            <div className="glass rounded-2xl px-4 py-3 shadow-2xl shadow-black/30">
              <p className="text-[0.65rem] font-bold tracking-wide text-white/70 uppercase">
                Kunjungan PNPP
              </p>
              <div className="mt-2 flex items-end gap-1.5">
                {[35, 55, 42, 70, 62, 88].map((height, index) => (
                  <motion.span
                    key={index}
                    initial={{ height: 0 }}
                    animate={{ height }}
                    transition={{ delay: 2.4 + index * 0.12, duration: 0.6, ease: 'easeOut' }}
                    className="w-2.5 rounded-t-sm bg-gradient-to-t from-primary to-gold"
                  />
                ))}
              </div>
            </div>
          </motion.div>
        </motion.div>
      </div>

      <div className="relative border-t border-white/10 bg-forest-ink/60 py-4 backdrop-blur">
        <div className="flex overflow-hidden [mask-image:linear-gradient(to_right,transparent,black_10%,black_90%,transparent)]">
          <div className="animate-marquee flex shrink-0 items-center gap-10 pr-10">
            {[...marqueeItems, ...marqueeItems].map((label, index) => (
              <span
                key={`${label}-${index}`}
                className="flex items-center gap-10 text-sm font-semibold tracking-[0.2em] whitespace-nowrap text-white/50 uppercase"
              >
                {label}
                <span className="size-1.5 rounded-full bg-gold/70" />
              </span>
            ))}
          </div>
        </div>
      </div>
    </section>
  )
}
