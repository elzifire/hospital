import { useEffect, useState } from 'react'
import { Menu } from 'lucide-react'
import { motion, useScroll, useSpring } from 'motion/react'
import { Button } from '@/components/ui/button'
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from '@/components/ui/sheet'
import { navLinks } from '@/data/content'
import { cn } from 'cn'

export function Navbar() {
  const [scrolled, setScrolled] = useState(false)
  const [open, setOpen] = useState(false)
  const [active, setActive] = useState('#beranda')
  const { scrollYProgress } = useScroll()
  const progress = useSpring(scrollYProgress, { stiffness: 120, damping: 30, mass: 0.4 })

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 24)
    onScroll()
    window.addEventListener('scroll', onScroll, { passive: true })
    return () => window.removeEventListener('scroll', onScroll)
  }, [])

  useEffect(() => {
    const sections = navLinks
      .map((link) => document.querySelector<HTMLElement>(link.href))
      .filter((el): el is HTMLElement => el !== null)
    const observer = new IntersectionObserver(
      (entries) => {
        for (const entry of entries) {
          if (entry.isIntersecting) setActive(`#${entry.target.id}`)
        }
      },
      { rootMargin: '-45% 0px -50% 0px' },
    )
    sections.forEach((section) => observer.observe(section))
    return () => observer.disconnect()
  }, [])

  return (
    <header className="fixed inset-x-0 top-0 z-50">
      <motion.div
        className="fixed inset-x-0 top-0 h-1 origin-left bg-gradient-to-r from-primary via-gold to-primary"
        style={{ scaleX: progress }}
      />
      <div
        className={cn(
          'transition-all duration-500',
          scrolled
            ? 'border-b border-forest/10 bg-white/85 shadow-lg shadow-forest/5 backdrop-blur-xl'
            : 'bg-transparent',
        )}
      >
        <nav className="mx-auto flex h-20 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
          <a href="#beranda" className="group flex items-center gap-3">
            <span className="relative">
              <img
                src="/image/logo-proaktif.jpeg"
                alt="Logo PROAKTIF"
                className="size-11 rounded-xl object-cover ring-2 ring-gold/70 transition-transform duration-300 group-hover:scale-105"
              />
            </span>
            <span className="leading-tight">
              <span
                className={cn(
                  'block text-lg font-extrabold tracking-tight transition-colors',
                  scrolled ? 'text-forest-ink' : 'text-white',
                )}
              >
                PROAKTIF
              </span>
              <span
                className={cn(
                  'block text-[0.65rem] font-semibold tracking-[0.18em] uppercase transition-colors',
                  scrolled ? 'text-primary' : 'text-gold',
                )}
              >
                RS Bhayangkara Bogor
              </span>
            </span>
          </a>

          <div className="hidden items-center gap-1 lg:flex">
            {navLinks.map((link) => (
              <a
                key={link.href}
                href={link.href}
                className={cn(
                  'relative rounded-full px-4 py-2 text-sm font-semibold transition-colors',
                  scrolled
                    ? 'text-forest-ink/70 hover:bg-primary/10 hover:text-primary'
                    : 'text-white/80 hover:bg-white/10 hover:text-white',
                  active === link.href &&
                    (scrolled ? 'bg-primary/10 text-primary' : 'bg-white/15 text-white'),
                )}
              >
                {link.label}
              </a>
            ))}
          </div>

          <div className="flex items-center gap-2">
            <Button
              asChild
              className="hidden bg-gold text-forest-ink shadow-lg shadow-gold/30 hover:bg-gold/90 lg:inline-flex"
            >
              <a href="#kontak">Hubungi Kami</a>
            </Button>
            <Sheet open={open} onOpenChange={setOpen}>
              <SheetTrigger asChild>
                <Button
                  variant="outline"
                  size="icon"
                  className={cn(
                    'size-10 rounded-xl lg:hidden',
                    scrolled
                      ? 'border-forest/15 bg-white/70 text-forest-ink'
                      : 'border-white/25 bg-white/10 text-white backdrop-blur',
                  )}
                >
                  <Menu />
                  <span className="sr-only">Buka menu</span>
                </Button>
              </SheetTrigger>
              <SheetContent side="right" className="w-80 bg-background">
                <SheetHeader>
                  <SheetTitle className="flex items-center gap-3">
                    <img
                      src="/image/logo-proaktif.jpeg"
                      alt="Logo PROAKTIF"
                      className="size-10 rounded-lg object-cover ring-2 ring-gold/70"
                    />
                    PROAKTIF
                  </SheetTitle>
                </SheetHeader>
                <div className="mt-6 flex flex-col gap-1">
                  {navLinks.map((link) => (
                    <a
                      key={link.href}
                      href={link.href}
                      onClick={() => setOpen(false)}
                      className={cn(
                        'rounded-xl px-4 py-3 text-sm font-semibold transition-colors',
                        active === link.href
                          ? 'bg-primary/10 text-primary'
                          : 'text-foreground/80 hover:bg-muted',
                      )}
                    >
                      {link.label}
                    </a>
                  ))}
                </div>
                <Button asChild className="mt-6 w-full bg-gold text-forest-ink hover:bg-gold/90">
                  <a href="#kontak" onClick={() => setOpen(false)}>
                    Hubungi Kami
                  </a>
                </Button>
              </SheetContent>
            </Sheet>
          </div>
        </nav>
      </div>
    </header>
  )
}
