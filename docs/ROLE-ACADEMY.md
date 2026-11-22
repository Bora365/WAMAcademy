# Akademi iş rolleri

Moodle 5.2 (`2026042002`) üzerinde dört iş ayrımı vardır. Site yöneticisi bir rol değildir; `siteadmins` listesindeki hesaptır. Diğer üçü roldür.

| İş | Nasıl kurulur | Atama bağlamı |
|---|---|---|
| Site yöneticisi | Site yönetimi → Kullanıcılar → Site yöneticileri | Sistem. `moodle/site:config` hiçbir role verilmez |
| Akademi yöneticisi | Rol kısa adı `academymanager` | Kurs kategorisi. Kurs bağlamı da açıktır; sisteme atamayın |
| Eğitmen | Mevcut `editingteacher` (Öğretmen) | Yalnız ilgili kurs |
| Öğrenci | Mevcut `student` | Yalnız ilgili kurs |

Standart `manager`, `coursecreator`, `teacher`, `guest`, `user` ve `frontpage` rolleri silinmez.

## Neden `manager` kullanılmadı

Varsayılan `manager` rolü bu iş için fazla geniştir. Yerel kurulumda bu rolün açık yetkileri arasında şunlar vardır:

- `moodle/course:delete` (kurs silme)
- `moodle/user:create` (yeni hesap açma)
- `enrol/manual:config` (kayıt yöntemi yapılandırma)
- Site ağacını görme (`moodle/site:configview`) ve yüzlerce başka yönetim yetkisi

Bu rolü yerinde daraltmak, Moodle’ın yönetici arketipini ve ileride bu role yapılacak atamaları da değiştirir. Rol sıfırlanırsa arketip yetkileri geri gelir. Bu yüzden arketipsiz, yalnız aşağıdaki izinleri taşıyan `academymanager` rolü oluşturulur. `manager` rolünün kayıtlarına dokunulmaz.

## Akademi yöneticisinin izinleri

ALLOW, sistem bağlamındaki rol tanımında. Atama kategoriye yapılır; yetki alttaki kurslara miras kalır.

- Kurs oluşturma ve görme: `moodle/course:create`, `moodle/course:view`, `moodle/course:viewhiddencourses`, `moodle/category:viewhiddencategories`
- Kurs ayarı: `moodle/course:update`, `moodle/course:visibility`, `moodle/course:changefullname`, `moodle/course:changeshortname`, `moodle/course:changesummary`, `moodle/course:changecategory`
- Katılımcılar: `moodle/course:viewparticipants`, `moodle/course:enrolreview`
- Mevcut hesabı kaydetme ve kaydı kaldırma: `enrol/manual:enrol`, `enrol/manual:unenrol`, `enrol/manual:manage`
- Rol atama: `moodle/role:assign`. Atayabileceği roller yalnız `editingteacher` ve `student`

Verilmeyenler: `moodle/site:config`, `moodle/site:configview`, `moodle/user:create`, `moodle/user:update`, `moodle/user:delete`, `moodle/course:delete`, `moodle/category:manage`, `moodle/role:manage`, `moodle/role:override`, `enrol/manual:config`, `moodle/course:enrolconfig`, `local/wamakademi:managevideos`.

Menü ve sayfalar bu capability’lere bakar. Kurs oluştur bağlantısı `moodle/course:create` ister. Site yönetimi `moodle/site:config` ister. Katılımcı ekleme `enrol/manual/ajax.php` içinde `moodle/course:enrolreview`, `moodle/role:assign` ve `enrol/manual:enrol` ister. Kayıt kaldırma `enrol/manual:unenrol` ister.

## Eğitmen sınırı

`editingteacher` rolünde şu yetkiler sistem bağlamında PREVENT yapılır: `enrol/manual:enrol`, `enrol/manual:unenrol`, `enrol/manual:manage`, `enrol/manual:config`, `moodle/course:enrolconfig`, `moodle/role:assign`.

Bu rolün `role_allow_assign` satırları silinir. İçerik, not ve `local/wamakademi:managevideos` durur. Video yükleme ayrıca `moodle/course:manageactivities`, `mod/resource:addinstance` ve `moodle/course:activityvisibility` ister; bunlar eğitmende kalır.

Öğrenci rolü değiştirilmez. Taslak, gizli etkinlik olduğu için `get_course_lessons.php` onu `uservisible` olmayan kullanıcıya vermez. Öğrencide `moodle/course:viewhiddenactivities` ve `local/wamakademi:managevideos` yoktur.

## Kursu açanın otomatik öğretmen olması

`course/edit.php`, kursu oluşturan kişiyi `creatornewroleid` (Öğretmen) ile kaydeder. Kişi kurs bağlamında `moodle/role:assign` görebiliyorsa bu kayıt yapılmaz. Akademi yöneticisi bu yetkiyi kategoriden miras aldığı için kursa öğretmen olarak yazılmaz.

Site yöneticisi bu kontrolden muaftır. `enroladminnewcourse` açıkken (yerelde `1`) site yöneticisi yeni kursa öğretmen olarak kaydolur. Bu davranış değiştirilmedi.

## Kurulum ve mevcut siteler

Eklenti sürümü `2026082611`.

Yeni kurulum `db/install.php` içinden `local_wamakademi\roles::sync()` çağırır.

Mevcut site:

```bash
php admin/cli/upgrade.php --non-interactive
```

Web kökü `public` ise komut Moodle kökünden, `admin/cli/upgrade.php` üzerinden çalışır. Yükseltme `roles::sync()` çalıştırır.

Moodle çekirdeği veya `enrol_manual` güncellemesi eğitmen arketipini yeniden ALLOW yaparsa sınırı tekrar uygulayın:

```bash
php public/local/wamakademi/cli/sync_roles.php
```

Komut rolü silmez. Yalnız `academymanager` izinlerini tanıma çeker ve eğitmen kayıt sınırını yeniden yazar. Kategori atamaları durur.

Rolü bir kategoriye atamak site yöneticisinin işidir. Sistem bağlamına atamayın. Yerel doğrulamada atama, kategori kimliği 1 üzerindedir.

## Geri alma

Eğitmen kayıt yetkilerini Moodle varsayılanına döndürmek (Öğretmen, düzenleme yapamayan eğitimci ve öğrenci atayabilir; kayıt capability’leri ALLOW):

```bash
php public/local/wamakademi/cli/sync_roles.php --restore-editingteacher
```

Sonraki `sync` veya eklenti yükseltmesi bu geri almayı yeniden kısıtlar.

Akademi yöneticisi rolünü ve bütün atamalarını silmek:

```bash
php public/local/wamakademi/cli/sync_roles.php --delete-academy-role --confirm
```

Ardından önbellek:

```bash
php admin/cli/purge_caches.php
```

## Bilinçli olarak verilmeyenler

- Kurs silme
- Kullanıcı hesabı oluşturma, askıya alma ve silme
- Kategori ağacını yönetme
- Kayıt yöntemini ekleme veya kapatma (`enrol/manual:config`, `moodle/course:enrolconfig`)
- Video yükleme ve etkinlik düzenleme
- Gizli eğitimin WAM kataloğunda listelenmesi. Katalog yalnız `visible=1` kursları gösterir. Akademi yöneticisi gizli kursu yetkisiyle açabilir; katalogda görmez

`teacher` (düzenleme yapamayan eğitimci) rolündeki video izni daha önceki Content Workflow değişikliğinden kalır. Bu turda kaldırılmadı. Dört iş rolünden biri değildir.
