import { Badge } from '@/components/ui/badge'
import { Reveal } from '@/components/shared/Reveal'
import { cn } from 'cn'

type SectionHeadingProps = {
  eyebrow: string
  title: string
  description?: string
  dark?: boolean
  align?: 'center' | 'left'
}

export function SectionHeading({
  eyebrow,
  title,
  description,
  dark = false,
  align = 'center',
}: SectionHeadingProps) {
  return (
    <Reveal className={cn('max-w-3xl', align === 'center' ? 'mx-auto text-center' : 'text-left')}>
      <Badge
        variant="outline"
        className={cn(
          'h-auto rounded-full px-3 py-1 text-xs font-semibold tracking-widest uppercase',
          dark
            ? 'border-gold/40 bg-gold/10 text-gold'
            : 'border-primary/30 bg-primary/5 text-primary',
        )}
      >
        {eyebrow}
      </Badge>
      <h2
        className={cn(
          'mt-4 text-3xl font-extrabold tracking-tight text-balance sm:text-4xl lg:text-[2.75rem] lg:leading-tight',
          dark ? 'text-white' : 'text-forest-ink',
        )}
      >
        {title}
      </h2>
      {description ? (
        <p
          className={cn(
            'mt-4 text-base leading-relaxed text-pretty sm:text-lg',
            dark ? 'text-white/70' : 'text-muted-foreground',
          )}
        >
          {description}
        </p>
      ) : null}
    </Reveal>
  )
}
