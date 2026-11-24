<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Turkish strings for local_wamakademi.
 *
 * @package    local_wamakademi
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'WAM Akademi';
$string['adminnav'] = 'Site yönetimi kategorileri';
$string['adminnavtitle'] = 'Site yönetimi';
$string['adminnavopen'] = 'Yönetim menüsü';
$string['adminnavclose'] = 'Yönetim menüsünü kapat';
$string['adminsearchlabel'] = 'Yönetim ayarlarında ara';
$string['adminsearchplaceholder'] = 'Yönetim ayarlarında ara…';
$string['adminsearchhint'] = 'Yalnızca bu sayfadaki bağlantıları değil, sitedeki tüm ayarları arar.';
$string['adminsearchsubmit'] = 'Ayarlarda ara';
$string['adminsearchresults'] = 'Arama sonuçları';
$string['adminsearchnoresults'] = 'Aramanızla eşleşen ayar bulunamadı';
$string['adminsearchnoresultshelp'] = 'Yazımı kontrol edin, daha kısa bir kelime deneyin veya yönetim menüsündeki kategorilere göz atın.';
$string['adminsearchclear'] = 'Aramayı temizle';
$string['adminhomedesc'] = 'Site genelindeki ayarları, kullanıcıları, eğitimleri, eklentileri ve raporları yönetin. Soldan bir kategori seçin ya da ayar arayın.';
$string['adminbreadcrumb'] = 'Yönetim';
$string['adminsearchcount'] = '{$a} eşleşen sayfa';
$string['adminsearchtooshort'] = 'Ayarlarda aramak için en az iki karakter yazın';
$string['courseaddparticipants'] = 'Katılımcı ekle';
$string['coursesearchlabel'] = 'Ad veya e-posta ile ara';
$string['coursesearchplaceholder'] = 'Ad, soyad veya e-posta…';
$string['coursesearchsubmit'] = 'Ara';
$string['coursesearchclear'] = 'Aramayı temizle';
$string['courseadvancedfilters'] = 'Gelişmiş filtreler';
$string['courseinitials'] = 'Baş harfe göre';
$string['courseindexshort'] = 'Eğitim dizini';
$string['participantsfirstname'] = 'Ad';
$string['participantslastname'] = 'Soyad';
$string['participantsemail'] = 'E-posta';
$string['participantsrole'] = 'Rol';
$string['participantsgroup'] = 'Grup';
$string['participantslastaccess'] = 'Son erişim';
$string['participantsstatus'] = 'Durum';
$string['participantsactions'] = 'İşlemler';
$string['participantsactionsfor'] = 'İşlemler: {$a}';
$string['participantseditroles'] = 'Rolü düzenle';
$string['participantseditgroups'] = 'Grubu düzenle';
$string['participantsenroldetails'] = 'Kayıt bilgileri';
$string['participantseditenrol'] = 'Kaydı düzenle';
$string['participantsunenrol'] = 'Eğitim kaydını kaldır';
$string['participantstablelabel'] = 'Katılımcılar tablosu';
$string['participantsedit'] = 'Düzenle';
$string['participantssave'] = 'Kaydet';
$string['participantsediting'] = 'Tablo düzenleme modunda';
$string['formrequired'] = 'Zorunlu';

// Kullanıcı ve katılımcı ekranları (User R1): bağlam adlandırma ve kapsam açıklamaları.
$string['participantsrolecourse'] = 'Eğitim rolü';
$string['participantsrolesite'] = 'Ana sayfa rolü';
$string['participantslastaccesscourse'] = 'Eğitime son erişim';
$string['participantslastaccesssite'] = 'Siteye son erişim';
$string['participantstitlecourse'] = '{$a} — Katılımcılar';
$string['participantstitlesite'] = 'Site ana sayfası — Katılımcılar';
$string['participantsscopecourse'] = 'Bu eğitime kayıtlı kullanıcılar; rol, grup, durum ve son erişim yalnızca bu eğitim için geçerlidir. “Katılımcı ekle” var olan bir hesabı eğitime kaydeder, yeni hesap oluşturmaz.';
$string['participantsscopesite'] = 'Sitedeki tüm kullanıcı hesapları; rol, grup ve son erişim site ana sayfası bağlamına aittir, eğitim kayıtlarını göstermez.';
$string['participantsscopesitenew'] = 'Yeni hesap oluşturmak için {$a} kullanılır.';
$string['userslisttitle'] = 'Sistem kullanıcıları';
$string['userslistscope'] = 'Sitedeki tüm kullanıcı hesapları. Eğitim kayıtları, eğitim rolleri ve gruplar her eğitimin Katılımcılar ekranından yönetilir.';
$string['usersdeleteaccount'] = 'Hesabı sil';
$string['usersnewscope'] = 'Yeni bir sistem hesabı oluşturur; hesabı bir eğitime kaydetmez. Eğitime kayıt için eğitimin Katılımcılar ekranındaki “Katılımcı ekle” kullanılır.';
$string['userseditscope'] = 'Sistem hesabının bilgilerini düzenler. Eğitim rolleri ve kayıtlar bu ekranda değişmez; eğitimin Katılımcılar ekranından yönetilir.';
$string['userseditheading'] = 'Kullanıcıyı düzenle — {$a}';

// İçerik iş akışı (Content Workflow R1).
$string['wamakademi:managevideos'] = 'Eğitim videolarını yükle, önizle ve yayımla';
$string['menucreatecourse'] = 'Eğitim oluştur';
$string['coursenumberauto'] = 'Bu numara bu ekran açılınca ayrıldı. Kaydetmez veya iptal ederseniz bir daha kullanılmaz.';
$string['coursenumbermanual'] = 'Eğitim numarası elle yazılamaz. Kayıt sırasında otomatik atanır.';
$string['coursenumberlocked'] = 'Eğitim numarası değiştirilemez.';
$string['courseimagepreview'] = 'Eğitimler sayfasında kapak böyle görünür. Görsel kartı dolduracak şekilde kırpılır.';
$string['menuuploadvideo'] = 'Video yükle';
$string['menueditcourse'] = 'Eğitim ayarları';
$string['menuaddcontent'] = 'İçerik ekle';
$string['menuaddassign'] = 'Ödev ekle';
$string['menuaddquiz'] = 'Sınav ekle';
$string['menuaddsectionprompt'] = 'Hangi bölüme eklensin?';
$string['menuaddsectionback'] = 'Geri';

$string['uploadtitle'] = 'Video yükle';
$string['uploadheading'] = '{$a} — Video yükle';
$string['uploadscope'] = 'MP4 veya MOV dosyasını gizli taslak olarak kaydeder; öğrenci görmez. Önizledikten sonra yayımlayabilirsiniz.';
$string['uploadcourse'] = 'Eğitim';
$string['uploadsection'] = 'Bölüm';
$string['uploadnewsection'] = 'Yeni bölüm adı (isteğe bağlı)';
$string['uploadlessonname'] = 'Ders adı';
$string['uploadintro'] = 'Açıklama (isteğe bağlı)';
$string['uploadfile'] = 'Video dosyası (MP4 veya MOV)';
$string['uploadonevideo'] = 'Tek seferde yalnızca 1 adet video dosyası yüklenebilir.';
$string['uploadvideotype'] = 'Yalnızca geçerli MP4 veya MOV video dosyaları kabul edilir.';
$string['uploaddraftsave'] = 'Taslak kaydet';
$string['uploadcancel'] = 'İptal';
$string['uploadsubmitting'] = 'Yükleniyor…';
$string['uploaddraftsaved'] = 'Video gizli taslak olarak kaydedildi.';
$string['uploadduplicate'] = 'Bu gönderim zaten işlendi; mevcut taslağa yönlendirildiniz.';
$string['uploadnoperm'] = 'Bu eğitimde video yönetme yetkiniz yok.';
$string['uploadnocourses'] = 'Video yükleyebileceğiniz bir eğitim bulunamadı.';
$string['previewstatusdraft'] = 'Taslak';
$string['previewstatuspublished'] = 'Yayında';
$string['previewstatusrestricted'] = 'Yayında ama öğrenciye kapalı';
$string['previewedit'] = 'Düzenle';
$string['previewpublish'] = 'Yayımla';
$string['previewunpublish'] = 'Yayından kaldır';
$string['previewback'] = 'Eğitime dön';
$string['previewopenplayer'] = 'WAM oynatıcıda aç';
$string['publishsuccess'] = 'İçerik yayımlandı; yetkili öğrenciler görebilir.';
$string['unpublishsuccess'] = 'İçerik yayından kaldırıldı; öğrenciler göremez.';
$string['publishforbidden'] = 'Yayımlama yetkiniz yok.';
$string['publishinvalid'] = 'Geçersiz istek.';
$string['hiddendrafts'] = 'Gizli taslaklar';
$string['norestriction'] = '';
$string['restrictioncourse'] = 'Eğitim gizli';
$string['restrictionsection'] = 'Bölüm gizli';
$string['restrictionavailability'] = 'Erişim kısıtı: {$a}';
$string['videostab'] = 'Videolar';
$string['videostitle'] = 'Videolar';
$string['videosempty'] = 'Bu eğitime henüz video eklenmedi';
$string['videospreview'] = 'Önizle';
$string['videosmoveup'] = 'Yukarı taşı';
$string['videosmovedown'] = 'Aşağı taşı';
$string['videosmoved'] = 'Video sırası güncellendi.';
$string['videosstatus'] = 'Durum';
$string['videosactions'] = 'Eylemler';
$string['videosorder'] = 'Sıra';
$string['cardmanagecourse'] = 'Eğitimi yönet';
$string['cardopencourse'] = 'Eğitimi aç';
$string['cardcontinue'] = 'Devam et';

// İlerleme ve ödev raporları.
$string['wamakademi:viewreports'] = 'Eğitim ilerleme ve ödev raporlarını görüntüle';
$string['reporttab'] = 'İlerleme';
$string['reportcoursetab'] = 'İlerleme raporu';
$string['reportcoursetitle'] = '{$a} — İlerleme raporu';
$string['reportcoursescope'] = 'Yalnızca bu eğitimdeki kayıtlı öğrencilerin video ilerlemesi ve ödev durumları. Gizli videolar dahil edilmez.';
$string['reportoverviewtitle'] = 'Eğitim özeti';
$string['reportoverviewscope'] = 'Yetkili olduğunuz eğitimlerdeki kayıt, başlangıç ve tamamlanma özeti. Departman veya şirket kırılımı yoktur.';
$string['reportoverviewlink'] = 'Tüm özet';
$string['reportnoperm'] = 'Bu raporu görüntüleme yetkiniz yok.';
$string['reporttabparticipants'] = 'Katılımcılar';
$string['reporttabassignments'] = 'Ödevler';
$string['reportsummary'] = 'Özet';
$string['reportenrolled'] = 'Kayıtlı';
$string['reportstatus'] = 'Durum';
$string['reportstatusall'] = 'Tümü';
$string['reportstatusassigned'] = 'Başlamadı';
$string['reportstatusactive'] = 'Devam ediyor';
$string['reportstatuscompleted'] = 'Tamamlandı';
$string['reportlessons'] = 'Ders';
$string['reportlesson'] = 'Ders';
$string['reportlessonsdone'] = 'Tamamlanan ders';
$string['reportprogress'] = 'İlerleme';
$string['reportparticipant'] = 'Katılımcı';
$string['reportlastactivity'] = 'Son etkinlik';
$string['reportactions'] = 'İşlem';
$string['reportviewvideos'] = 'Videolar';
$string['reportuserdetail'] = 'Kullanıcı videoları';
$string['reportvideostatus'] = 'Video durumu';
$string['reportwatched'] = 'İzlenen';
$string['reportsearchuser'] = 'Kullanıcı ara';
$string['reportsearchuserph'] = 'Ad veya e-posta…';
$string['reportapplyfilters'] = 'Uygula';
$string['reportclearfilters'] = 'Temizle';
$string['reportexport'] = 'CSV indir';
$string['reportempty'] = 'Bu filtrelerle gösterilecek katılımcı yok.';
$string['reportemptyassign'] = 'Bu eğitimde ödev kaydı yok veya filtreyle eşleşen satır yok.';
$string['reportviewtoggle'] = 'Görünüm';
$string['reportviewtable'] = 'Tablo';
$string['reportviewchart'] = 'Grafik';
$string['reportchartprogress'] = 'Katılımcı ilerlemeleri';
$string['reportchartstatus'] = 'Durum dağılımı';
$string['reportchartassign'] = 'Ödev durum dağılımı';
$string['reportchartpie'] = 'Pasta grafik';
$string['reportchartcombo'] = 'Karma grafik';
$string['reportchartadvanced'] = 'İleri grafik';
$string['reportchartshownextpie'] = 'Pasta grafik';
$string['reportchartshownextcombo'] = 'Karma grafik';
$string['reportchartshowtable'] = 'Tablo';
$string['reportchartbarcompleted'] = 'Tamamlanan ders';
$string['reportchartbarremaining'] = 'Kalan ders';
$string['reportchartlinelessons'] = 'Ders tamamlama %';
$string['reportchartbarcount'] = 'Kayıt sayısı';
$string['reportchartlineratio'] = 'Oran %';
$string['reportchartstudentlist'] = 'Öğrenci ilerlemeleri';
$string['reportchartassignlist'] = 'Ödev satırları';
$string['reportchartseriesbars'] = 'Sütun';
$string['reportchartseriesline'] = 'Çizgi';
$string['reportchartseriesboth'] = 'Birlikte';
$string['reportchartseriesnextbars'] = 'Sütun';
$string['reportchartseriesnextline'] = 'Çizgi';
$string['reportchartseriesnextboth'] = 'Birlikte';
$string['reportemptyoverview'] = 'Raporlayabileceğiniz bir eğitim bulunamadı.';
$string['reportassignment'] = 'Ödev';
$string['reportdue'] = 'Son tarih';
$string['reportsubmitted'] = 'Teslim';
$string['reportgrade'] = 'Not';
$string['reportassignmissing'] = 'Teslim yok';
$string['reportassigndraft'] = 'Taslak';
$string['reportassignsubmitted'] = 'Teslim edildi';
$string['reportassignlate'] = 'Geç teslim';
$string['reportassigngraded'] = 'Notlandırıldı';
$string['reportcourses'] = 'Eğitim';
$string['reportcourse'] = 'Eğitim';
$string['reportcategory'] = 'Kategori';
$string['reportopencourse'] = 'Detay';

// Değerlendirme raporları.
$string['evalreportheading'] = 'Değerlendirme raporları';
$string['evalreporttitle'] = '{$a} — Değerlendirme raporları';
$string['evalreportscope'] = 'Bu eğitimdeki sınav ve ödevlerin konu/ölçüt başarısı ile not dağılımı. Puanı olmayan deneme veya teslim dağılıma girmez.';
$string['evalreportlink'] = 'Değerlendirme raporları';
$string['evalreportpickactivity'] = 'Etkinlik seçin';
$string['evalreportnoactivities'] = 'Bu eğitimde gösterilecek demo sınav veya ödev yok.';
$string['evalreporttypequiz'] = 'Sınav';
$string['evalreporttypeassign'] = 'Ödev';
$string['evalreportquiztopics'] = 'Konu başarısı';
$string['evalreportquizdist'] = 'Not dağılımı';
$string['evalreportassigncriteria'] = 'Ölçüt başarısı';
$string['evalreportassigndist'] = 'Not dağılımı';
$string['evalreporttable'] = 'Tablo';
$string['evalreportchart'] = 'Grafik';
$string['evalreportviewtoggle'] = 'Görünüm';
$string['evalreportempty'] = 'Henüz puanlanan öğrenci yok.';
$string['evalreportlabel'] = 'Kalem';
$string['evalreportvalue'] = 'Değer';
$string['evalreportdetail'] = 'Açıklama';
$string['evalreportaverage'] = 'Ortalama';
$string['evalreportpass'] = 'Geçme';
$string['evalreportstatussummary'] = 'Durum özeti';
$string['evalreportstatusmissing'] = 'Teslim yok';
$string['evalreportstatuspending'] = 'Puan bekliyor';
$string['evalreportstatusgraded'] = 'Puanlandı';

// Gelişmiş puanlama — eğitmen not yönetim merkezi.
$string['gradinghubheading'] = 'Gelişmiş puanlama';
$string['gradinghubtitle'] = '{$a} — Gelişmiş puanlama';
$string['gradinghublead'] = 'Bu etkinlikte not verme, gönderim/sonuç yönetimi ve sınıf notlarına buradan ulaşın.';
$string['gradinghubempty'] = 'Bu etkinlik için görüntüleyebileceğiniz bir not yönetimi eylemi yok.';
$string['gradinghubsubmissions'] = 'Gönderimleri notlandır';
$string['gradinghubsubmissionsdesc'] = 'Öğrenci teslimlerini listeleyin, filtreleyin ve not verin.';
$string['gradinghubgrader'] = 'Notlandırma paneli';
$string['gradinghubgraderdesc'] = 'Öğrenciler arasında geçiş yaparak hızlı notlandırın.';
$string['gradinghubdownload'] = 'Tüm gönderimleri indir';
$string['gradinghubdownloaddesc'] = 'Teslim edilen dosyaları toplu olarak indirin.';
$string['gradinghuboverrides'] = 'Geçersiz kılmalar';
$string['gradinghuboverridesdesc'] = 'Belirli kullanıcılar için tarih veya deneme kurallarını değiştirin.';
$string['gradinghubmethod'] = 'Puanlama yöntemi';
$string['gradinghubmethoddesc'] = 'Rubrik veya puanlama kılavuzu tanımlayın / düzenleyin.';
$string['gradinghubquizresults'] = 'Sınav sonuçları';
$string['gradinghubquizresultsdesc'] = 'Denemeleri ve notları genel sonuçlar tablosunda görün.';
$string['gradinghubquizmanual'] = 'Elle notlandırma';
$string['gradinghubquizmanualdesc'] = 'Açık uçlu veya manuel puanlanan soruları değerlendirin.';
$string['gradinghubquizresponses'] = 'Yanıtlar';
$string['gradinghubquizresponsesdesc'] = 'Öğrenci yanıtlarını satır satır inceleyin.';
$string['gradinghubquizstats'] = 'İstatistikler';
$string['gradinghubquizstatsdesc'] = 'Soru ve sınav istatistiklerini görüntüleyin.';
$string['gradinghubgradebook'] = 'Kurs not defteri';
$string['gradinghubgradebookdesc'] = 'Bu eğitimdeki tüm öğrencilerin notlarını tek tabloda görün.';
$string['gradinghubevaldesc'] = 'Konu / ölçüt başarısı ve not dağılımı (WAM raporu).';
$string['gradinghubprogress'] = 'İlerleme raporu';
$string['gradinghubprogressdesc'] = 'Kurs ilerleme ve ödev teslim durumlarına gidin.';

// Üst menü — Ödevler / Sınavlar panosu.
$string['navassignments'] = 'Ödevler';
$string['navquizzes'] = 'Sınavlar';
$string['assessboardassignstitle'] = 'Ödevler';
$string['assessboardassignslead'] = 'Kayıtlı eğitimlerindeki ödevleri tek listede görün. Eğitmen olduğunuz satırlarda değerlendirme ve rapora geçin.';
$string['assessboardquiztitle'] = 'Sınavlar';
$string['assessboardquizlead'] = 'Kayıtlı eğitimlerindeki sınavları tek listede görün. Eğitmen olduğunuz satırlarda sonuç ve değerlendirmeye geçin.';
$string['assessboardsummary'] = 'Özet';
$string['assessboardstattotal'] = 'Toplam';
$string['assessboardstatpending'] = 'Bekleyen';
$string['assessboardstatsubmitted'] = 'Teslim / deneme';
$string['assessboardstatgraded'] = 'Notlandı';
$string['assessboardsearch'] = 'Ara';
$string['assessboardsearchph'] = 'Etkinlik veya eğitim…';
$string['assessboardallcourses'] = 'Tüm eğitimler';
$string['assessboardactivity'] = 'Etkinlik';
$string['assessboardmetric'] = 'Özet';
$string['assessboardteacher'] = 'Eğitmen';
$string['assessboardgrade'] = 'Değerlendir';
$string['assessboardreport'] = 'Rapor';
$string['assessboardopen'] = 'Aç';
$string['assessboardemptyassign'] = 'Gösterilecek ödev yok. Filtreleri temizleyin veya bir eğitime kaydolun.';
$string['assessboardemptyquiz'] = 'Gösterilecek sınav yok. Filtreleri temizleyin veya bir eğitime kaydolun.';
$string['assessboardmetricassign'] = '{$a->submitted} teslim · {$a->graded} notlandı · {$a->pending} bekliyor';
$string['assessboardmetricquiz'] = '{$a->attempts} deneme · {$a->users} kişi · {$a->finished} bitti';
$string['assessboardstatepending'] = 'Bekliyor';
$string['assessboardstatesubmitted'] = 'Teslim var';
$string['assessboardstategraded'] = 'Notlandı';
$string['assessboardstateinprogress'] = 'Devam ediyor';
$string['assessboardstatemissing'] = 'Başlanmadı';
$string['assessboardstatedraft'] = 'Taslak';
$string['assessboardstatelate'] = 'Geç';
$string['assessboardstatecompleted'] = 'Tamamlandı';
$string['assessboardstateattempted'] = 'Denendi';
$string['assessboardstateassigned'] = 'Atandı';

