<?php
/**
 * Starter layouts for the ID card designer.
 *
 * Each preset is a plain front/back layout in CARD PIXELS (CR80 @ 96dpi):
 * landscape = 324 x 204, portrait = 204 x 324. Elements use exactly the same
 * shape the designer saves, so applying one is a straight assignment — no
 * conversion anywhere.
 *
 * Element keys:
 *   kind     field | text | photo | qr | signature
 *   text     for field/text; field text holds a {{ placeholder }}
 *   x,y,w    position and width; h only where the kind needs it
 *   fontSize card px; align left|center|right; bold/italic/underline
 *   color    hex; bg + pad optional (filled chip behind text)
 *
 * Deliberately background-free: these define structure only, so a background
 * image can be dropped in afterwards without fighting the layout.
 */

// Placeholder helper — kept out of the designer's OB/CB constants on purpose,
// since this file is rendered to JSON, not into the JS source.
$T = fn (string $name): string => '{{' . $name . '}}';

// Shared building blocks, so the presets stay readable.
$hdr = fn (string $txt, int $y, int $size = 7, string $color = '#0f172a') => [
    'kind' => 'text', 'text' => $txt, 'x' => 0, 'y' => $y, 'w' => 324,
    'fontSize' => $size, 'align' => 'center', 'bold' => true, 'color' => $color,
];

return [

// ─────────────────────────────────────────────────────────────────────────
[
    'key'   => 'classic-landscape',
    'name'  => 'Classic Landscape',
    'desc'  => 'Photo left, details right. The familiar government ID look.',
    'front_orientation' => 'landscape',
    'back_orientation'  => 'landscape',
    'front' => [
        $hdr('REPUBLIC OF THE PHILIPPINES', 8, 6, '#475569'),
        ['kind'=>'field','text'=>$T('municipality'),'x'=>0,'y'=>17,'w'=>324,'fontSize'=>7,'align'=>'center','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('brgy_name'),'x'=>0,'y'=>27,'w'=>324,'fontSize'=>9,'align'=>'center','bold'=>true,'color'=>'#1d4ed8'],
        ['kind'=>'text','text'=>'RESIDENT IDENTIFICATION CARD','x'=>0,'y'=>40,'w'=>324,'fontSize'=>5,'align'=>'center','bold'=>false,'color'=>'#64748b'],

        ['kind'=>'photo','x'=>16,'y'=>56,'w'=>66,'h'=>78],

        ['kind'=>'text','text'=>'NAME','x'=>94,'y'=>58,'w'=>60,'fontSize'=>4,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('full_name'),'x'=>94,'y'=>65,'w'=>215,'fontSize'=>9,'align'=>'left','bold'=>true,'color'=>'#0f172a'],

        ['kind'=>'text','text'=>'ADDRESS','x'=>94,'y'=>82,'w'=>60,'fontSize'=>4,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('address'),'x'=>94,'y'=>89,'w'=>215,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#334155'],

        ['kind'=>'text','text'=>'BIRTH DATE','x'=>94,'y'=>110,'w'=>60,'fontSize'=>4,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('bday'),'x'=>94,'y'=>117,'w'=>95,'fontSize'=>6,'align'=>'left','bold'=>false,'color'=>'#0f172a'],

        ['kind'=>'text','text'=>'SEX','x'=>196,'y'=>110,'w'=>40,'fontSize'=>4,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('gender'),'x'=>196,'y'=>117,'w'=>50,'fontSize'=>6,'align'=>'left','bold'=>false,'color'=>'#0f172a'],

        ['kind'=>'text','text'=>'ID NO.','x'=>252,'y'=>110,'w'=>60,'fontSize'=>4,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('id_no'),'x'=>252,'y'=>117,'w'=>60,'fontSize'=>6,'align'=>'left','bold'=>true,'color'=>'#1d4ed8'],

        ['kind'=>'field','text'=>$T('valid_until'),'x'=>94,'y'=>140,'w'=>140,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#64748b'],
    ],
    'back' => [
        ['kind'=>'text','text'=>'EMERGENCY CONTACT','x'=>16,'y'=>16,'w'=>180,'fontSize'=>6,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('ic_name'),'x'=>16,'y'=>28,'w'=>180,'fontSize'=>6,'align'=>'left','bold'=>false,'color'=>'#334155'],
        ['kind'=>'field','text'=>$T('ic_relationship'),'x'=>16,'y'=>39,'w'=>180,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#64748b'],
        ['kind'=>'field','text'=>$T('ic_contact'),'x'=>16,'y'=>49,'w'=>180,'fontSize'=>6,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('ic_address'),'x'=>16,'y'=>60,'w'=>180,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#64748b'],

        ['kind'=>'qr','x'=>236,'y'=>22,'w'=>72],

        ['kind'=>'signature','x'=>16,'y'=>120,'w'=>96,'h'=>26],
        ['kind'=>'text','text'=>'Signature of Bearer','x'=>16,'y'=>149,'w'=>96,'fontSize'=>4,'align'=>'center','bold'=>false,'color'=>'#94a3b8'],

        ['kind'=>'field','text'=>$T('captain'),'x'=>196,'y'=>132,'w'=>112,'fontSize'=>6,'align'=>'center','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('captain_pos'),'x'=>196,'y'=>143,'w'=>112,'fontSize'=>4,'align'=>'center','bold'=>false,'color'=>'#94a3b8'],
    ],
],

// ─────────────────────────────────────────────────────────────────────────
[
    'key'   => 'modern-portrait',
    'name'  => 'Modern Portrait',
    'desc'  => 'Vertical card, large photo on top, centred details.',
    'front_orientation' => 'portrait',
    'back_orientation'  => 'portrait',
    'front' => [
        ['kind'=>'field','text'=>$T('brgy_name'),'x'=>0,'y'=>12,'w'=>204,'fontSize'=>8,'align'=>'center','bold'=>true,'color'=>'#1d4ed8'],
        ['kind'=>'field','text'=>$T('municipality'),'x'=>0,'y'=>24,'w'=>204,'fontSize'=>5,'align'=>'center','bold'=>false,'color'=>'#64748b'],

        ['kind'=>'photo','x'=>62,'y'=>40,'w'=>80,'h'=>94],

        ['kind'=>'field','text'=>$T('full_name'),'x'=>10,'y'=>144,'w'=>184,'fontSize'=>9,'align'=>'center','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('id_no'),'x'=>10,'y'=>158,'w'=>184,'fontSize'=>6,'align'=>'center','bold'=>false,'color'=>'#1d4ed8'],

        ['kind'=>'text','text'=>'ADDRESS','x'=>16,'y'=>178,'w'=>80,'fontSize'=>4,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('address'),'x'=>16,'y'=>185,'w'=>172,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#334155'],

        ['kind'=>'text','text'=>'BIRTH DATE','x'=>16,'y'=>206,'w'=>80,'fontSize'=>4,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('bday'),'x'=>16,'y'=>213,'w'=>85,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#0f172a'],
        ['kind'=>'text','text'=>'SEX','x'=>112,'y'=>206,'w'=>60,'fontSize'=>4,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('gender'),'x'=>112,'y'=>213,'w'=>60,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#0f172a'],

        ['kind'=>'field','text'=>$T('valid_until'),'x'=>10,'y'=>240,'w'=>184,'fontSize'=>5,'align'=>'center','bold'=>false,'color'=>'#64748b'],
    ],
    'back' => [
        ['kind'=>'qr','x'=>62,'y'=>20,'w'=>80],
        ['kind'=>'field','text'=>$T('id_no'),'x'=>10,'y'=>106,'w'=>184,'fontSize'=>5,'align'=>'center','bold'=>false,'color'=>'#64748b'],

        ['kind'=>'text','text'=>'IN CASE OF EMERGENCY','x'=>16,'y'=>130,'w'=>172,'fontSize'=>5,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('ic_name'),'x'=>16,'y'=>142,'w'=>172,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#334155'],
        ['kind'=>'field','text'=>$T('ic_contact'),'x'=>16,'y'=>152,'w'=>172,'fontSize'=>5,'align'=>'left','bold'=>true,'color'=>'#0f172a'],

        ['kind'=>'signature','x'=>54,'y'=>214,'w'=>96,'h'=>26],
        ['kind'=>'field','text'=>$T('captain'),'x'=>16,'y'=>244,'w'=>172,'fontSize'=>5,'align'=>'center','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('captain_pos'),'x'=>16,'y'=>254,'w'=>172,'fontSize'=>4,'align'=>'center','bold'=>false,'color'=>'#94a3b8'],
    ],
],

// ─────────────────────────────────────────────────────────────────────────
[
    'key'   => 'minimal',
    'name'  => 'Minimal',
    'desc'  => 'Only the essentials — name, photo, ID number. Lots of white space.',
    'front_orientation' => 'landscape',
    'back_orientation'  => 'landscape',
    'front' => [
        ['kind'=>'field','text'=>$T('brgy_name'),'x'=>20,'y'=>20,'w'=>200,'fontSize'=>6,'align'=>'left','bold'=>true,'color'=>'#64748b'],
        ['kind'=>'photo','x'=>20,'y'=>44,'w'=>62,'h'=>74],
        ['kind'=>'field','text'=>$T('full_name'),'x'=>96,'y'=>62,'w'=>210,'fontSize'=>11,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('id_no'),'x'=>96,'y'=>82,'w'=>210,'fontSize'=>6,'align'=>'left','bold'=>false,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('address'),'x'=>96,'y'=>100,'w'=>210,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#64748b'],
        ['kind'=>'field','text'=>$T('valid_until'),'x'=>20,'y'=>170,'w'=>160,'fontSize'=>4,'align'=>'left','bold'=>false,'color'=>'#cbd5e1'],
    ],
    'back' => [
        ['kind'=>'qr','x'=>20,'y'=>44,'w'=>72],
        ['kind'=>'field','text'=>$T('ic_name'),'x'=>110,'y'=>60,'w'=>190,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#334155'],
        ['kind'=>'field','text'=>$T('ic_contact'),'x'=>110,'y'=>72,'w'=>190,'fontSize'=>6,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'signature','x'=>110,'y'=>120,'w'=>96,'h'=>24],
    ],
],

// ─────────────────────────────────────────────────────────────────────────
[
    'key'   => 'banner-header',
    'name'  => 'Banner Header',
    'desc'  => 'Solid colour band across the top with the barangay name reversed out.',
    'front_orientation' => 'landscape',
    'back_orientation'  => 'landscape',
    'front' => [
        ['kind'=>'text','text'=>' ','x'=>0,'y'=>0,'w'=>324,'fontSize'=>14,'align'=>'left','bold'=>false,'color'=>'#ffffff','bg'=>'#1d4ed8','pad'=>6],
        ['kind'=>'field','text'=>$T('brgy_name'),'x'=>12,'y'=>8,'w'=>200,'fontSize'=>9,'align'=>'left','bold'=>true,'color'=>'#ffffff'],
        ['kind'=>'field','text'=>$T('municipality'),'x'=>12,'y'=>21,'w'=>200,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#bfdbfe'],
        ['kind'=>'text','text'=>'RESIDENT ID','x'=>212,'y'=>13,'w'=>100,'fontSize'=>6,'align'=>'right','bold'=>true,'color'=>'#ffffff'],

        ['kind'=>'photo','x'=>18,'y'=>52,'w'=>64,'h'=>76],
        ['kind'=>'field','text'=>$T('full_name'),'x'=>94,'y'=>56,'w'=>214,'fontSize'=>9,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('address'),'x'=>94,'y'=>74,'w'=>214,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#475569'],
        ['kind'=>'field','text'=>$T('bday'),'x'=>94,'y'=>98,'w'=>100,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#334155'],
        ['kind'=>'field','text'=>$T('gender'),'x'=>200,'y'=>98,'w'=>50,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#334155'],
        ['kind'=>'field','text'=>$T('id_no'),'x'=>94,'y'=>120,'w'=>120,'fontSize'=>7,'align'=>'left','bold'=>true,'color'=>'#1d4ed8'],
        ['kind'=>'field','text'=>$T('valid_until'),'x'=>18,'y'=>168,'w'=>160,'fontSize'=>4,'align'=>'left','bold'=>false,'color'=>'#94a3b8'],
    ],
    'back' => [
        ['kind'=>'text','text'=>' ','x'=>0,'y'=>0,'w'=>324,'fontSize'=>10,'align'=>'left','bold'=>false,'color'=>'#ffffff','bg'=>'#1d4ed8','pad'=>4],
        ['kind'=>'text','text'=>'EMERGENCY CONTACT','x'=>12,'y'=>6,'w'=>200,'fontSize'=>6,'align'=>'left','bold'=>true,'color'=>'#ffffff'],
        ['kind'=>'field','text'=>$T('ic_name'),'x'=>16,'y'=>40,'w'=>190,'fontSize'=>6,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('ic_relationship'),'x'=>16,'y'=>52,'w'=>190,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#64748b'],
        ['kind'=>'field','text'=>$T('ic_contact'),'x'=>16,'y'=>63,'w'=>190,'fontSize'=>6,'align'=>'left','bold'=>true,'color'=>'#1d4ed8'],
        ['kind'=>'qr','x'=>240,'y'=>38,'w'=>68],
        ['kind'=>'signature','x'=>16,'y'=>128,'w'=>96,'h'=>24],
        ['kind'=>'field','text'=>$T('captain'),'x'=>196,'y'=>134,'w'=>112,'fontSize'=>5,'align'=>'center','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('captain_pos'),'x'=>196,'y'=>144,'w'=>112,'fontSize'=>4,'align'=>'center','bold'=>false,'color'=>'#94a3b8'],
    ],
],

// ─────────────────────────────────────────────────────────────────────────
[
    'key'   => 'photo-right',
    'name'  => 'Photo Right',
    'desc'  => 'Mirror of the classic — details left, photo and QR on the right.',
    'front_orientation' => 'landscape',
    'back_orientation'  => 'landscape',
    'front' => [
        ['kind'=>'field','text'=>$T('brgy_name'),'x'=>16,'y'=>14,'w'=>200,'fontSize'=>8,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('municipality'),'x'=>16,'y'=>26,'w'=>200,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#64748b'],

        ['kind'=>'text','text'=>'NAME','x'=>16,'y'=>52,'w'=>60,'fontSize'=>4,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('full_name'),'x'=>16,'y'=>59,'w'=>200,'fontSize'=>9,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'text','text'=>'ADDRESS','x'=>16,'y'=>78,'w'=>60,'fontSize'=>4,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('address'),'x'=>16,'y'=>85,'w'=>200,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#334155'],
        ['kind'=>'field','text'=>$T('bday'),'x'=>16,'y'=>110,'w'=>95,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#334155'],
        ['kind'=>'field','text'=>$T('gender'),'x'=>118,'y'=>110,'w'=>50,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#334155'],
        ['kind'=>'field','text'=>$T('id_no'),'x'=>16,'y'=>132,'w'=>140,'fontSize'=>7,'align'=>'left','bold'=>true,'color'=>'#1d4ed8'],

        ['kind'=>'photo','x'=>240,'y'=>48,'w'=>66,'h'=>78],
        ['kind'=>'field','text'=>$T('valid_until'),'x'=>228,'y'=>134,'w'=>80,'fontSize'=>4,'align'=>'center','bold'=>false,'color'=>'#94a3b8'],
    ],
    'back' => [
        ['kind'=>'qr','x'=>236,'y'=>30,'w'=>72],
        ['kind'=>'text','text'=>'EMERGENCY','x'=>16,'y'=>24,'w'=>180,'fontSize'=>5,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('ic_name'),'x'=>16,'y'=>36,'w'=>190,'fontSize'=>6,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('ic_contact'),'x'=>16,'y'=>48,'w'=>190,'fontSize'=>6,'align'=>'left','bold'=>false,'color'=>'#334155'],
        ['kind'=>'field','text'=>$T('ic_address'),'x'=>16,'y'=>60,'w'=>190,'fontSize'=>4,'align'=>'left','bold'=>false,'color'=>'#64748b'],
        ['kind'=>'signature','x'=>16,'y'=>124,'w'=>96,'h'=>24],
        ['kind'=>'field','text'=>$T('captain'),'x'=>196,'y'=>130,'w'=>112,'fontSize'=>5,'align'=>'center','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('captain_pos'),'x'=>196,'y'=>140,'w'=>112,'fontSize'=>4,'align'=>'center','bold'=>false,'color'=>'#94a3b8'],
    ],
],

// ─────────────────────────────────────────────────────────────────────────
[
    'key'   => 'senior-citizen',
    'name'  => 'Senior Citizen',
    'desc'  => 'Larger type throughout for easy reading. Fewer fields.',
    'front_orientation' => 'landscape',
    'back_orientation'  => 'landscape',
    'front' => [
        ['kind'=>'text','text'=>'SENIOR CITIZEN','x'=>0,'y'=>10,'w'=>324,'fontSize'=>8,'align'=>'center','bold'=>true,'color'=>'#b45309'],
        ['kind'=>'field','text'=>$T('brgy_name'),'x'=>0,'y'=>23,'w'=>324,'fontSize'=>6,'align'=>'center','bold'=>false,'color'=>'#64748b'],

        ['kind'=>'photo','x'=>18,'y'=>46,'w'=>70,'h'=>84],
        ['kind'=>'field','text'=>$T('full_name'),'x'=>100,'y'=>54,'w'=>206,'fontSize'=>12,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('bday'),'x'=>100,'y'=>78,'w'=>206,'fontSize'=>8,'align'=>'left','bold'=>false,'color'=>'#334155'],
        ['kind'=>'field','text'=>$T('address'),'x'=>100,'y'=>96,'w'=>206,'fontSize'=>6,'align'=>'left','bold'=>false,'color'=>'#475569'],
        ['kind'=>'field','text'=>$T('id_no'),'x'=>100,'y'=>120,'w'=>206,'fontSize'=>9,'align'=>'left','bold'=>true,'color'=>'#b45309'],
        ['kind'=>'field','text'=>$T('valid_until'),'x'=>18,'y'=>168,'w'=>180,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#94a3b8'],
    ],
    'back' => [
        ['kind'=>'text','text'=>'EMERGENCY CONTACT','x'=>16,'y'=>20,'w'=>200,'fontSize'=>7,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('ic_name'),'x'=>16,'y'=>34,'w'=>200,'fontSize'=>8,'align'=>'left','bold'=>true,'color'=>'#334155'],
        ['kind'=>'field','text'=>$T('ic_contact'),'x'=>16,'y'=>50,'w'=>200,'fontSize'=>9,'align'=>'left','bold'=>true,'color'=>'#b45309'],
        ['kind'=>'field','text'=>$T('ic_relationship'),'x'=>16,'y'=>68,'w'=>200,'fontSize'=>6,'align'=>'left','bold'=>false,'color'=>'#64748b'],
        ['kind'=>'qr','x'=>240,'y'=>28,'w'=>68],
        ['kind'=>'signature','x'=>16,'y'=>126,'w'=>110,'h'=>28],
        ['kind'=>'field','text'=>$T('captain'),'x'=>190,'y'=>132,'w'=>118,'fontSize'=>5,'align'=>'center','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('captain_pos'),'x'=>190,'y'=>142,'w'=>118,'fontSize'=>4,'align'=>'center','bold'=>false,'color'=>'#94a3b8'],
    ],
],

// ─────────────────────────────────────────────────────────────────────────
[
    'key'   => 'voter-id',
    'name'  => 'Voter ID',
    'desc'  => 'Precinct number featured. For barangay electoral use.',
    'front_orientation' => 'landscape',
    'back_orientation'  => 'landscape',
    'front' => [
        ['kind'=>'field','text'=>$T('brgy_name'),'x'=>0,'y'=>10,'w'=>324,'fontSize'=>8,'align'=>'center','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'text','text'=>'VOTER IDENTIFICATION','x'=>0,'y'=>23,'w'=>324,'fontSize'=>5,'align'=>'center','bold'=>false,'color'=>'#64748b'],

        ['kind'=>'photo','x'=>18,'y'=>44,'w'=>62,'h'=>74],
        ['kind'=>'field','text'=>$T('full_name'),'x'=>92,'y'=>48,'w'=>216,'fontSize'=>9,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('address'),'x'=>92,'y'=>66,'w'=>216,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#475569'],
        ['kind'=>'field','text'=>$T('bday'),'x'=>92,'y'=>88,'w'=>100,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#334155'],
        ['kind'=>'field','text'=>$T('gender'),'x'=>196,'y'=>88,'w'=>50,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#334155'],

        ['kind'=>'text','text'=>'PRECINCT','x'=>92,'y'=>110,'w'=>70,'fontSize'=>4,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('precinct_no'),'x'=>92,'y'=>118,'w'=>90,'fontSize'=>10,'align'=>'left','bold'=>true,'color'=>'#b91c1c'],
        ['kind'=>'text','text'=>'ID NO.','x'=>196,'y'=>110,'w'=>60,'fontSize'=>4,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('id_no'),'x'=>196,'y'=>118,'w'=>110,'fontSize'=>7,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('since'),'x'=>18,'y'=>168,'w'=>160,'fontSize'=>4,'align'=>'left','bold'=>false,'color'=>'#94a3b8'],
    ],
    'back' => [
        ['kind'=>'qr','x'=>18,'y'=>30,'w'=>72],
        ['kind'=>'field','text'=>$T('precinct_no'),'x'=>18,'y'=>108,'w'=>72,'fontSize'=>6,'align'=>'center','bold'=>true,'color'=>'#b91c1c'],
        ['kind'=>'text','text'=>'EMERGENCY CONTACT','x'=>110,'y'=>30,'w'=>198,'fontSize'=>5,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('ic_name'),'x'=>110,'y'=>42,'w'=>198,'fontSize'=>6,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('ic_contact'),'x'=>110,'y'=>54,'w'=>198,'fontSize'=>6,'align'=>'left','bold'=>false,'color'=>'#334155'],
        ['kind'=>'signature','x'=>110,'y'=>118,'w'=>96,'h'=>24],
        ['kind'=>'field','text'=>$T('captain'),'x'=>212,'y'=>124,'w'=>96,'fontSize'=>5,'align'=>'center','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('captain_pos'),'x'=>212,'y'=>134,'w'=>96,'fontSize'=>4,'align'=>'center','bold'=>false,'color'=>'#94a3b8'],
    ],
],

// ─────────────────────────────────────────────────────────────────────────
[
    'key'   => 'qr-forward',
    'name'  => 'QR Forward',
    'desc'  => 'QR code on the front for fast scanning at events and checkpoints.',
    'front_orientation' => 'landscape',
    'back_orientation'  => 'landscape',
    'front' => [
        ['kind'=>'field','text'=>$T('brgy_name'),'x'=>16,'y'=>14,'w'=>180,'fontSize'=>7,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'photo','x'=>16,'y'=>40,'w'=>58,'h'=>68],
        ['kind'=>'field','text'=>$T('full_name'),'x'=>84,'y'=>44,'w'=>150,'fontSize'=>8,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('id_no'),'x'=>84,'y'=>60,'w'=>150,'fontSize'=>6,'align'=>'left','bold'=>false,'color'=>'#1d4ed8'],
        ['kind'=>'field','text'=>$T('address'),'x'=>84,'y'=>74,'w'=>150,'fontSize'=>4,'align'=>'left','bold'=>false,'color'=>'#64748b'],
        ['kind'=>'field','text'=>$T('bday'),'x'=>84,'y'=>94,'w'=>150,'fontSize'=>4,'align'=>'left','bold'=>false,'color'=>'#64748b'],

        ['kind'=>'qr','x'=>244,'y'=>40,'w'=>64],
        ['kind'=>'text','text'=>'SCAN TO VERIFY','x'=>244,'y'=>108,'w'=>64,'fontSize'=>4,'align'=>'center','bold'=>false,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('valid_until'),'x'=>16,'y'=>170,'w'=>180,'fontSize'=>4,'align'=>'left','bold'=>false,'color'=>'#cbd5e1'],
    ],
    'back' => [
        ['kind'=>'text','text'=>'EMERGENCY CONTACT','x'=>16,'y'=>22,'w'=>200,'fontSize'=>6,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('ic_name'),'x'=>16,'y'=>36,'w'=>200,'fontSize'=>6,'align'=>'left','bold'=>false,'color'=>'#334155'],
        ['kind'=>'field','text'=>$T('ic_relationship'),'x'=>16,'y'=>48,'w'=>200,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#64748b'],
        ['kind'=>'field','text'=>$T('ic_contact'),'x'=>16,'y'=>59,'w'=>200,'fontSize'=>6,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('ic_address'),'x'=>16,'y'=>71,'w'=>200,'fontSize'=>4,'align'=>'left','bold'=>false,'color'=>'#94a3b8'],
        ['kind'=>'signature','x'=>16,'y'=>126,'w'=>96,'h'=>24],
        ['kind'=>'field','text'=>$T('captain'),'x'=>196,'y'=>132,'w'=>112,'fontSize'=>5,'align'=>'center','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('captain_pos'),'x'=>196,'y'=>142,'w'=>112,'fontSize'=>4,'align'=>'center','bold'=>false,'color'=>'#94a3b8'],
    ],
],

// ─────────────────────────────────────────────────────────────────────────
[
    'key'   => 'two-column',
    'name'  => 'Two Column',
    'desc'  => 'Labels and values in tidy columns. Fits the most information.',
    'front_orientation' => 'landscape',
    'back_orientation'  => 'landscape',
    'front' => [
        ['kind'=>'field','text'=>$T('brgy_name'),'x'=>0,'y'=>10,'w'=>324,'fontSize'=>7,'align'=>'center','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('municipality'),'x'=>0,'y'=>21,'w'=>324,'fontSize'=>4,'align'=>'center','bold'=>false,'color'=>'#94a3b8'],

        ['kind'=>'photo','x'=>16,'y'=>40,'w'=>60,'h'=>72],

        ['kind'=>'text','text'=>'NAME','x'=>88,'y'=>42,'w'=>44,'fontSize'=>4,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('full_name'),'x'=>136,'y'=>42,'w'=>172,'fontSize'=>6,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'text','text'=>'BIRTH','x'=>88,'y'=>56,'w'=>44,'fontSize'=>4,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('bday'),'x'=>136,'y'=>56,'w'=>172,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#334155'],
        ['kind'=>'text','text'=>'SEX','x'=>88,'y'=>68,'w'=>44,'fontSize'=>4,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('gender'),'x'=>136,'y'=>68,'w'=>172,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#334155'],
        ['kind'=>'text','text'=>'ADDRESS','x'=>88,'y'=>80,'w'=>44,'fontSize'=>4,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('address'),'x'=>136,'y'=>80,'w'=>172,'fontSize'=>4,'align'=>'left','bold'=>false,'color'=>'#334155'],
        ['kind'=>'text','text'=>'CONTACT','x'=>88,'y'=>98,'w'=>44,'fontSize'=>4,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('contact'),'x'=>136,'y'=>98,'w'=>172,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#334155'],
        ['kind'=>'text','text'=>'ID NO.','x'=>88,'y'=>112,'w'=>44,'fontSize'=>4,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('id_no'),'x'=>136,'y'=>112,'w'=>172,'fontSize'=>6,'align'=>'left','bold'=>true,'color'=>'#1d4ed8'],

        ['kind'=>'field','text'=>$T('valid_until'),'x'=>16,'y'=>170,'w'=>180,'fontSize'=>4,'align'=>'left','bold'=>false,'color'=>'#cbd5e1'],
    ],
    'back' => [
        ['kind'=>'text','text'=>'IN CASE OF EMERGENCY','x'=>16,'y'=>20,'w'=>200,'fontSize'=>5,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'text','text'=>'NAME','x'=>16,'y'=>36,'w'=>50,'fontSize'=>4,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('ic_name'),'x'=>70,'y'=>36,'w'=>150,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#334155'],
        ['kind'=>'text','text'=>'RELATION','x'=>16,'y'=>48,'w'=>50,'fontSize'=>4,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('ic_relationship'),'x'=>70,'y'=>48,'w'=>150,'fontSize'=>5,'align'=>'left','bold'=>false,'color'=>'#334155'],
        ['kind'=>'text','text'=>'CONTACT','x'=>16,'y'=>60,'w'=>50,'fontSize'=>4,'align'=>'left','bold'=>true,'color'=>'#94a3b8'],
        ['kind'=>'field','text'=>$T('ic_contact'),'x'=>70,'y'=>60,'w'=>150,'fontSize'=>5,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'qr','x'=>244,'y'=>30,'w'=>64],
        ['kind'=>'signature','x'=>16,'y'=>126,'w'=>96,'h'=>24],
        ['kind'=>'field','text'=>$T('captain'),'x'=>196,'y'=>132,'w'=>112,'fontSize'=>5,'align'=>'center','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('captain_pos'),'x'=>196,'y'=>142,'w'=>112,'fontSize'=>4,'align'=>'center','bold'=>false,'color'=>'#94a3b8'],
    ],
],

// ─────────────────────────────────────────────────────────────────────────
[
    'key'   => 'blank-guides',
    'name'  => 'Blank + Guides',
    'desc'  => 'Just a header and photo box. Start here to design your own.',
    'front_orientation' => 'landscape',
    'back_orientation'  => 'landscape',
    'front' => [
        ['kind'=>'field','text'=>$T('brgy_name'),'x'=>0,'y'=>14,'w'=>324,'fontSize'=>8,'align'=>'center','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'photo','x'=>18,'y'=>48,'w'=>62,'h'=>74],
        ['kind'=>'field','text'=>$T('full_name'),'x'=>94,'y'=>56,'w'=>214,'fontSize'=>9,'align'=>'left','bold'=>true,'color'=>'#0f172a'],
        ['kind'=>'field','text'=>$T('id_no'),'x'=>94,'y'=>76,'w'=>214,'fontSize'=>6,'align'=>'left','bold'=>false,'color'=>'#64748b'],
    ],
    'back' => [
        ['kind'=>'qr','x'=>126,'y'=>40,'w'=>72],
        ['kind'=>'signature','x'=>114,'y'=>134,'w'=>96,'h'=>24],
    ],
],

];
