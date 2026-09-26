import {
  BellRing,
  CalendarCheck,
  ClipboardCheck,
  Database,
  GraduationCap,
  HandHeart,
  LayoutDashboard,
  LineChart,
  Megaphone,
  MessagesSquare,
  ScrollText,
  ShieldCheck,
  Signal,
  Sparkles,
  TrendingUp,
  Users,
  type LucideIcon,
} from 'lucide-react'

export const navLinks = [
  { href: '#beranda', label: 'Beranda' },
  { href: '#tentang', label: 'Tentang' },
  { href: '#manfaat', label: 'Manfaat' },
  { href: '#alur', label: 'Alur Kerja' },
  { href: '#output', label: 'Output' },
  { href: '#kontak', label: 'Kontak' },
]

export const pillars: { icon: LucideIcon; title: string; description: string }[] = [
  {
    icon: MessagesSquare,
    title: 'Komunikasi Digital',
    description:
      'WhatsApp Business resmi, media sosial, dan kanal digital lain sebagai sarana penyampaian informasi layanan kesehatan.',
  },
  {
    icon: Database,
    title: 'Database PNPP',
    description:
      'Data tervalidasi meliputi identitas, nomor telepon, satuan kerja, hingga kebutuhan pelayanan kesehatan setiap PNPP.',
  },
  {
    icon: BellRing,
    title: 'Sistem Pengingat',
    description:
      'Digital reminder jadwal kontrol berkala dan pelayanan kesehatan lainnya yang terkirim otomatis sesuai jadwal.',
  },
  {
    icon: GraduationCap,
    title: 'Media Edukasi Kesehatan',
    description:
      'Flyer, poster digital, video edukasi, dan materi promosi layanan kesehatan untuk kegiatan promotif dan preventif.',
  },
  {
    icon: LayoutDashboard,
    title: 'Dashboard Monitoring',
    description:
      'Rekap seluruh aktivitas outreach digital dan data kunjungan PNPP untuk evaluasi serta pengambilan keputusan berbasis data.',
  },
]

export const comparison = {
  before: {
    title: 'Layanan Pasif',
    subtitle: 'Sebelum PROAKTIF',
    points: [
      'Rumah sakit menunggu pasien datang',
      'Informasi layanan tersebar dan terbatas',
      'Jadwal kontrol berkala mudah terlewat',
      'Data PNPP belum terintegrasi',
      'Evaluasi layanan tanpa data realtime',
    ],
  },
  after: {
    title: 'Layanan PROAKTIF',
    subtitle: 'Outreach Digital Terintegrasi',
    points: [
      'RS menjangkau PNPP secara proaktif',
      'Informasi layanan & jadwal dokter tersampaikan berkala',
      'Pengingat kontrol otomatis tepat waktu',
      'Database PNPP Polresta Bogor terintegrasi',
      'Dashboard monitoring untuk keputusan berbasis data',
    ],
  },
}

export const benefits: { icon: LucideIcon; title: string; description: string }[] = [
  {
    icon: MessagesSquare,
    title: 'Komunikasi Efektif',
    description:
      'Efektivitas komunikasi antara rumah sakit dengan PNPP meningkat melalui kanal digital resmi yang terpercaya.',
  },
  {
    icon: Signal,
    title: 'Akses Informasi Luas',
    description:
      'Akses informasi pelayanan kesehatan, jadwal praktik dokter, dan program kesehatan terbuka lebih luas.',
  },
  {
    icon: CalendarCheck,
    title: 'Kepatuhan Kontrol',
    description:
      'Kepatuhan PNPP terhadap pemeriksaan kesehatan berkala meningkat berkat pengingat digital yang konsisten.',
  },
  {
    icon: TrendingUp,
    title: 'Kunjungan Meningkat',
    description:
      'Mendorong peningkatan kunjungan pasien melalui edukasi dan informasi layanan yang berkelanjutan.',
  },
  {
    icon: HandHeart,
    title: 'Layanan Optimal',
    description:
      'Optimalisasi pemanfaatan layanan yang berdampak pada peningkatan pendapatan serta mutu rumah sakit.',
  },
  {
    icon: ShieldCheck,
    title: 'Dedikasi untuk PNPP',
    description:
      'Kehadiran negara dalam menjaga kesehatan Pegawai Negeri Pada Polri beserta keluarganya secara berkelanjutan.',
  },
]

export const steps: { icon: LucideIcon; title: string; description: string }[] = [
  {
    icon: Database,
    title: 'Registrasi Database PNPP',
    description:
      'Pengumpulan dan validasi data PNPP sebagai sasaran outreach digital: identitas, nomor telepon, satuan kerja, serta kebutuhan pelayanan kesehatan.',
  },
  {
    icon: Megaphone,
    title: 'Pengelolaan Media Outreach Digital',
    description:
      'Tim PROAKTIF mengelola WhatsApp Business resmi, media sosial, dan media komunikasi digital lainnya sebagai sarana penyampaian informasi.',
  },
  {
    icon: MessagesSquare,
    title: 'Penyampaian Informasi Layanan',
    description:
      'Sistem menyampaikan jadwal praktik dokter, layanan unggulan, fasilitas penunjang, serta kegiatan promotif dan preventif secara berkala.',
  },
  {
    icon: BellRing,
    title: 'Digital Reminder',
    description:
      'Sistem mengirimkan pengingat kepada PNPP terkait jadwal kontrol dan pelayanan kesehatan lainnya sesuai jadwal yang telah ditetapkan.',
  },
  {
    icon: LayoutDashboard,
    title: 'Dashboard Monitoring PROAKTIF',
    description:
      'Seluruh aktivitas outreach digital dan data kunjungan PNPP direkap dalam dashboard monitoring untuk memudahkan evaluasi pimpinan.',
  },
  {
    icon: LineChart,
    title: 'Monitoring dan Evaluasi',
    description:
      'Evaluasi berkala terhadap efektivitas penyampaian informasi, tingkat keterjangkauan outreach, dan peningkatan kunjungan sebagai dasar perbaikan berkelanjutan.',
  },
]

export const stats = [
  { value: 60, suffix: '', label: 'Hari Off Campus', detail: 'Masa implementasi inovasi' },
  { value: 6, suffix: '', label: 'Tahapan Pelaksanaan', detail: 'Alur kerja terintegrasi' },
  { value: 10, suffix: '', label: 'Output Inovasi', detail: 'Dokumen & sistem dihasilkan' },
  { value: 5, suffix: '', label: 'Pilar Terintegrasi', detail: 'Dalam satu ekosistem' },
]

export const outputs = [
  {
    icon: Users,
    title: 'Sprin Tim Efektif PROAKTIF',
    description: 'Terbentuknya Surat Perintah Tim Efektif PROAKTIF sebagai pelaksana inovasi.',
  },
  {
    icon: LayoutDashboard,
    title: 'Sistem PROAKTIF',
    description: 'Layanan outreach digital terintegrasi untuk Pegawai Negeri Pada Polri (PNPP).',
  },
  {
    icon: ScrollText,
    title: 'SOP Outreach Digital',
    description: 'Standar Operasional Prosedur Layanan Outreach Digital Terintegrasi (PROAKTIF).',
  },
  {
    icon: ShieldCheck,
    title: 'Keputusan Karumkit',
    description: 'Legalitas Keputusan Karumkit RS Bhayangkara Bogor tentang implementasi PROAKTIF beserta SOP.',
  },
  {
    icon: Database,
    title: 'Database PNPP Terintegrasi',
    description: 'Database PNPP Satker Polresta Bogor sebagai dasar pelaksanaan layanan outreach digital.',
  },
  {
    icon: LineChart,
    title: 'Dashboard Monitoring',
    description: 'Menyajikan data outreach digital, respons pengguna, dan kunjungan PNPP secara berkala.',
  },
  {
    icon: Sparkles,
    title: 'Media Edukasi Digital',
    description: 'Flyer, poster digital, video edukasi, dan materi promosi layanan kesehatan.',
  },
  {
    icon: ClipboardCheck,
    title: 'Laporan Monev',
    description: 'Laporan monitoring dan evaluasi implementasi memuat capaian indikator dan perkembangan kunjungan.',
  },
  {
    icon: ScrollText,
    title: 'Usulan SKP',
    description: 'Usulan penggunaan PROAKTIF ke dalam Sasaran Kinerja Pegawai (SKP).',
  },
  {
    icon: HandHeart,
    title: 'Komitmen Keberlanjutan',
    description: 'Surat Pernyataan Komitmen Keberlanjutan PROAKTIF pasca masa Off Campus.',
  },
]

export const marqueeItems = [
  'Outreach Proaktif',
  'Terintegrasi Digital',
  'Pelayanan Berkualitas',
  'Dedikasi untuk PNPP',
  'Sehat Bersama',
  'Melayani dengan Hati',
]
