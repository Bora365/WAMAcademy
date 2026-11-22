/**
 * Ölçülecek ekran kataloğu — keşif + önceki envanterlerle sabit kimlikler.
 * id: benzersiz ekran kimliği (kullanıcı/kurs id ayrı sayılmaz)
 */
export const SCREENS = [
  // Auth
  { id: 'AUTH-LOGIN', path: '/login/index.php', roles: ['guest'], kind: 'auth', allowLogin: true, task: 'Giriş formunu kullan' },

  // Learner shell
  { id: 'HOME-WAM', path: '/local/wamakademi/index.php', roles: ['student', 'teacher', 'admin'], kind: 'shell', task: 'Ana sayfayı tara, menüyü aç' },
  { id: 'CATALOG', path: '/local/wamakademi/catalog.php', roles: ['student', 'teacher', 'admin'], kind: 'list', task: 'Ara ve filtrele' },
  { id: 'MYCOURSES', path: '/local/wamakademi/mycourses.php', roles: ['student', 'teacher', 'admin'], kind: 'list', task: 'Kayıtlı kursları gör' },
  { id: 'CALENDAR-WAM', path: '/local/wamakademi/calendar.php', roles: ['student', 'teacher', 'admin'], kind: 'calendar', task: 'Takvimi gez' },
  { id: 'DASH-MY', path: '/my/', roles: ['admin'], kind: 'shell', task: 'Kontrol panelini gör' },

  // Course
  { id: 'COURSE-VIEW', path: '/course/view.php?id=2', roles: ['student', 'teacher', 'admin'], kind: 'course', task: 'Ders listesine gir' },
  { id: 'COURSE-PARTICIPANTS', path: '/user/index.php?id=2', roles: ['teacher', 'admin'], kind: 'table', task: 'Katılımcı satır menüsünü aç' },
  { id: 'COURSE-EDIT', path: '/course/edit.php?id=2', roles: ['teacher', 'admin'], kind: 'form', task: 'Alan ve editörü kullan' },
  { id: 'COURSE-GRADES', path: '/grade/report/grader/index.php?id=2', roles: ['teacher', 'admin'], kind: 'table', task: 'Not tablosunu tara' },
  { id: 'COURSE-COMPLETION', path: '/course/completion.php?id=2', roles: ['teacher', 'admin'], kind: 'form', task: 'Tamamlanma ayarını gör' },

  // Content
  { id: 'UPLOAD-VIDEO', path: '/local/wamakademi/upload.php?courseid=2&sectionnum=0', roles: ['teacher', 'admin'], kind: 'form', task: 'Yükleme formuna ulaş' },
  { id: 'LESSON-RESOURCE', path: null, roles: ['student', 'teacher', 'admin'], kind: 'player', task: 'Dersi aç ve içeriği kullan', resolve: 'firstResource' },
  { id: 'PPTX-VIEW', path: '/local/wamakademi/pptx_view.php', roles: ['student', 'teacher', 'admin'], kind: 'player', task: 'Sunumu aç', optional: true },

  // Messaging
  { id: 'MESSAGES', path: '/message/index.php', roles: ['student', 'teacher', 'admin'], kind: 'messages', task: 'Mesaj listesini aç' },
  { id: 'NOTIFICATIONS-POPOVER', path: '/local/wamakademi/index.php', roles: ['student', 'teacher', 'admin'], kind: 'shell', state: 'notifications-open', task: 'Bildirim zilini aç', skipMeasureNav: true },

  // Account (Faz 4 listesi — tek kimlik, çok rol)
  { id: 'ACC-PROFILE', path: '/user/profile.php', roles: ['student', 'teacher', 'admin'], kind: 'account', task: 'Profili gör', selfId: true },
  { id: 'ACC-EDIT', path: '/user/edit.php', roles: ['student', 'teacher'], kind: 'form', task: 'Son alan ve Kaydet bölgesine ulaş', selfId: true, edit: true },
  { id: 'ACC-EDIT-ADV', path: '/user/editadvanced.php', roles: ['admin'], kind: 'form', task: 'Gelişmiş profil formunu kullan', selfId: true, edit: true },
  { id: 'ACC-PREFS', path: '/user/preferences.php', roles: ['student', 'teacher', 'admin'], kind: 'account', task: 'Cark tercihler hub', },
  { id: 'ACC-PASSWORD', path: '/login/change_password.php', roles: ['student', 'teacher', 'admin'], kind: 'form', task: 'Sifre formunu gor (kaydetme)', },
  { id: 'ACC-LANGUAGE', path: '/user/language.php', roles: ['student', 'teacher', 'admin'], kind: 'form', task: 'Dil select kullan', },
  { id: 'ACC-EDITOR', path: '/user/editor.php', roles: ['student', 'teacher', 'admin'], kind: 'form', task: 'Editor tercihi select', },
  { id: 'ACC-FORUM', path: '/user/forum.php', roles: ['student', 'teacher', 'admin'], kind: 'form', task: 'Forum tercihi', },
  { id: 'ACC-MESSAGE-PREFS', path: '/message/edit.php', roles: ['student', 'teacher', 'admin'], kind: 'form', task: 'Ileti tercihi', },
  { id: 'ACC-NOTIF-PREFS', path: '/message/notificationpreferences.php', roles: ['student', 'teacher', 'admin'], kind: 'form', task: 'Bildirim toggle gor', },
  { id: 'ACC-CAL-PREFS', path: '/user/calendar.php', roles: ['student', 'teacher', 'admin'], kind: 'form', task: 'Takvim tercihi', },
  { id: 'ACC-CONTENTBANK', path: '/user/contentbank.php', roles: ['student', 'teacher', 'admin'], kind: 'form', task: 'Icerik bankasi tercihi', },
  { id: 'ACC-FILES', path: '/user/files.php', roles: ['student', 'teacher', 'admin'], kind: 'files', task: 'Dosya alanina ulas', },
  { id: 'ACC-SESSIONS', path: '/report/usersessions/user.php', roles: ['student', 'teacher', 'admin'], kind: 'table', task: 'Oturum tablosunu gor', },
  { id: 'ACC-GRADES', path: '/grade/report/overview/index.php', roles: ['student', 'teacher', 'admin'], kind: 'table', task: 'Basari notlari', },
  { id: 'ACC-BADGES', path: '/badges/mybadges.php', roles: ['student', 'teacher', 'admin'], kind: 'list', task: 'Rozet listesi', },
  { id: 'ACC-BADGE-PREFS', path: '/badges/preferences.php', roles: ['student', 'teacher', 'admin'], kind: 'form', task: 'Rozet tercihi', },
  { id: 'ACC-BACKPACK', path: '/badges/mybackpack.php', roles: ['student', 'teacher', 'admin'], kind: 'form', task: 'Backpack formu (baglanma yok)', },

  // Admin categories (separate screens)
  { id: 'ADM-SEARCH', path: '/admin/search.php', roles: ['admin'], kind: 'admin', task: 'Ayar ara' },
  { id: 'ADM-USERS', path: '/admin/category.php?category=users', roles: ['admin'], kind: 'admin', task: 'Kullanıcılar kategorisi' },
  { id: 'ADM-COURSES', path: '/admin/category.php?category=courses', roles: ['admin'], kind: 'admin', task: 'Kurslar kategorisi' },
  { id: 'ADM-GRADES', path: '/admin/category.php?category=grades', roles: ['admin'], kind: 'admin', task: 'Notlar kategorisi' },
  { id: 'ADM-APPEARANCE', path: '/admin/category.php?category=appearance', roles: ['admin'], kind: 'admin', task: 'Görünüm kategorisi' },
  { id: 'ADM-PLUGINS', path: '/admin/category.php?category=modules', roles: ['admin'], kind: 'admin', task: 'Eklentiler' },
  { id: 'ADM-SERVER', path: '/admin/category.php?category=server', roles: ['admin'], kind: 'admin', task: 'Sunucu' },
  { id: 'ADM-REPORTS', path: '/admin/category.php?category=reports', roles: ['admin'], kind: 'admin', task: 'Raporlar' },
  { id: 'ADM-DEVELOPMENT', path: '/admin/category.php?category=development', roles: ['admin'], kind: 'admin', task: 'Geliştirme' },
  { id: 'ADM-SECURITY', path: '/admin/category.php?category=security', roles: ['admin'], kind: 'admin', task: 'Güvenlik' },
  { id: 'ADM-UNSUPPORTED', path: '/admin/category.php?category=unsupported', roles: ['admin'], kind: 'admin', task: 'Desteklenmeyen', optional: true },

  // Admin key pages
  { id: 'ADM-SYSUSERS', path: '/admin/user.php', roles: ['admin'], kind: 'table', task: 'Sistem kullanıcıları filtre' },
  { id: 'ADM-USER-NEW', path: '/user/editadvanced.php?id=-1', roles: ['admin'], kind: 'form', task: 'Yeni kullanıcı formu' },
  { id: 'ADM-UPLOADUSER', path: '/admin/tool/uploaduser/index.php', roles: ['admin'], kind: 'form', task: 'Kullanıcı yükleme' },
  { id: 'ADM-COURSE-MGMT', path: '/course/management.php', roles: ['admin'], kind: 'admin', task: 'Kurs yönetimi' },
  { id: 'ADM-COURSE-NEW', path: '/course/edit.php?category=1', roles: ['admin', 'teacher'], kind: 'form', task: 'Kurs oluştur' },
  { id: 'ADM-UPLOADCOURSE', path: '/admin/tool/uploadcourse/index.php', roles: ['admin'], kind: 'form', task: 'Kurs yükleme' },
  { id: 'ADM-CATEGORY-EDIT', path: '/course/editcategory.php?id=1', roles: ['admin'], kind: 'form', task: 'Kategori ayarı' },
  { id: 'ADM-SETTINGS', path: '/admin/settings.php?section=frontpagesettings', roles: ['admin'], kind: 'form', task: 'Ön sayfa ayarları' },
  { id: 'ADM-THEME', path: '/admin/settings.php?section=themesettingboost', roles: ['admin'], kind: 'form', task: 'Boost tema ayarı' },
  { id: 'ADM-REPORTBUILDER', path: '/reportbuilder/index.php', roles: ['admin'], kind: 'table', task: 'Rapor oluşturucu' },

  // Site front tabs (course id 1)
  { id: 'SITE-PARTICIPANTS', path: '/user/index.php?id=1', roles: ['admin'], kind: 'table', task: 'Site katılımcıları' },
];

export const LAYER_A = ['phone390', 'ipadPort', 'desk1440'];
export const LAYER_B_EXTRA = ['phone320', 'phone360', 'phoneLand', 'phoneKb', 'ipadLand', 'split507', 'desk1024', 'desk1280'];
