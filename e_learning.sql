-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 21 Okt 2024 pada 03.09
-- Versi server: 10.4.28-MariaDB
-- Versi PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `e_learning`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `daftar`
--

CREATE TABLE `daftar` (
  `nama` varchar(25) NOT NULL,
  `password` varchar(25) NOT NULL,
  `sebagai` varchar(10) NOT NULL,
  `email` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `daftar`
--

INSERT INTO `daftar` (`nama`, `password`, `sebagai`, `email`) VALUES
('RYAN ADI SAPUTRA', '', '', 'email'),
('siswa', '$2y$10$TojNg1swzYYUldCrD6', 'guru', 'da@gmail.com');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nama` varchar(30) NOT NULL,
  `password` varchar(255) NOT NULL,
  `sebagai` varchar(25) NOT NULL,
  `email` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `nama`, `password`, `sebagai`, `email`) VALUES
(12, 'guru', '$2y$10$V/lqRc4Xzk65MDklr1Zp/uk9ency4NdxFvLnGYsmHQ0cHN9tJXsoq', 'guru', 'guruku2@gmail.com'),
(14, 'admin', '$2y$10$//n7ahiVfWBjNumRX/F8ZuM7NK8SrtGAEu/dwQPY3t8eYowhpPHfC', 'admin', 'admin2@gmail.com'),
(15, 'siswa', '$2y$10$OnlP2cj.fKrNGGgtVvt4iODTT62wxbdulq7HHVk.DwNvfcpXOfHPa', 'siswa', 'siswa@gmail.com');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
