-- ModalCalc: data proyek (projects, investors, operational_costs)
SET FOREIGN_KEY_CHECKS=0;
SET NAMES utf8mb4;

INSERT INTO `projects` (`id`, `user_id`, `nama_proyek`, `mode_input`, `jumlah_unit`, `harga_beli`, `harga_jual`, `total_modal`, `total_hasil_jual`, `persen_pemodal`, `persen_operator`, `nama_operator`, `catatan`, `status`, `completed_at`, `created_at`, `updated_at`) VALUES (1,1,'Server','unit',30,8000000,15000000,240000000,450000000,55.00,45.00,'Ramadhan',NULL,'completed','2026-07-06 18:29:27','2026-07-06 18:02:29','2026-07-06 18:29:27');
INSERT INTO `projects` (`id`, `user_id`, `nama_proyek`, `mode_input`, `jumlah_unit`, `harga_beli`, `harga_jual`, `total_modal`, `total_hasil_jual`, `persen_pemodal`, `persen_operator`, `nama_operator`, `catatan`, `status`, `completed_at`, `created_at`, `updated_at`) VALUES (2,1,'Ipad','unit',20,4500000,5200000,90000000,104000000,60.00,40.00,'Andi Nurhasana',NULL,'active',NULL,'2026-07-07 05:16:00','2026-07-07 05:16:00');
INSERT INTO `investors` (`id`, `project_id`, `nama`, `modal`, `urutan`) VALUES (3,1,'Alfian',200000000,0);
INSERT INTO `investors` (`id`, `project_id`, `nama`, `modal`, `urutan`) VALUES (4,1,'Anna',40000000,1);
INSERT INTO `investors` (`id`, `project_id`, `nama`, `modal`, `urutan`) VALUES (5,2,'Ulin Nuha',50000000,0);
INSERT INTO `investors` (`id`, `project_id`, `nama`, `modal`, `urutan`) VALUES (6,2,'Alfian',40000000,1);
INSERT INTO `operational_costs` (`id`, `project_id`, `keterangan`, `jumlah`, `urutan`) VALUES (1,2,'Cukai 150.000x16',2400000,0);

SET FOREIGN_KEY_CHECKS=1;
