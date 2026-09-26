import { Navbar } from '@/components/layout/Navbar'
import { Footer } from '@/components/layout/Footer'
import { About } from '@/components/sections/About'
import { Benefits } from '@/components/sections/Benefits'
import { Comparison } from '@/components/sections/Comparison'
import { Contact } from '@/components/sections/Contact'
import { Deliverables } from '@/components/sections/Deliverables'
import { Hero } from '@/components/sections/Hero'
import { HowItWorks } from '@/components/sections/HowItWorks'
import { Stats } from '@/components/sections/Stats'

export default function App() {
  return (
    <div className="min-h-svh bg-background text-foreground antialiased">
      <Navbar />
      <main>
        <Hero />
        <About />
        <Comparison />
        <Benefits />
        <HowItWorks />
        <Stats />
        <Deliverables />
        <Contact />
      </main>
      <Footer />
    </div>
  )
}
