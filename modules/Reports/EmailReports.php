<?php
/**
 * Weekly SEO email reports.
 *
 * @package SeofymeSEO
 */

namespace SeofymeSEO\Modules\Reports;

use SeofymeSEO\Modules\NotFound\NotFoundMonitor;
use SeofymeSEO\Modules\RankTracker\RankTracker;
use SeofymeSEO\Modules\SiteAudit\SiteAudit;
use SeofymeSEO\Support\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sends a simple weekly summary.
 */
class EmailReports {

	public const HOOK = 'seofyme_weekly_seo_report';

	/**
	 * Register.
	 *
	 * @return void
	 */
	public function register() {
		add_action( self::HOOK, array( $this, 'send' ) );
		add_action( 'admin_init', array( $this, 'maybe_schedule' ) );
	}

	/**
	 * Schedule cron.
	 *
	 * @return void
	 */
	public function maybe_schedule() {
		if ( ! Options::get( 'email_reports', false ) ) {
			$timestamp = wp_next_scheduled( self::HOOK );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, self::HOOK );
			}
			return;
		}
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'weekly', self::HOOK );
		}
	}

	/**
	 * Build + send report.
	 *
	 * @return void
	 */
	public function send() {
		if ( ! Options::get( 'email_reports', false ) ) {
			return;
		}
		$to = Options::get( 'report_email', '' );
		if ( ! $to ) {
			$to = get_option( 'admin_email' );
		}
		$audit   = ( new SiteAudit() )->run();
		$good    = count( array_filter( $audit, static function ( $c ) { return 'good' === $c['status']; } ) );
		$ranks   = ( new RankTracker() )->all();
		$notfound = class_exists( NotFoundMonitor::class ) ? ( new NotFoundMonitor() )->all( 5 ) : array();

		$lines   = array();
		$lines[] = sprintf( 'Seofyme SEO weekly report for %s', home_url( '/' ) );
		$lines[] = '';
		$lines[] = sprintf( 'Site audit: %d / %d checks healthy', $good, count( $audit ) );
		$lines[] = sprintf( 'Tracked keywords: %d', count( $ranks ) );
		if ( $ranks ) {
			$lines[] = 'Top tracked:';
			foreach ( array_slice( $ranks, 0, 5 ) as $row ) {
				$lines[] = sprintf( '- %s → position %s', $row['keyword'], $row['position'] );
			}
		}
		if ( $notfound ) {
			$lines[] = '';
			$lines[] = 'Top 404s:';
			foreach ( $notfound as $row ) {
				$lines[] = sprintf( '- %s (%s hits)', $row['url'], $row['hits'] );
			}
		}
		$lines[] = '';
		$lines[] = admin_url( 'admin.php?page=seofyme-seo' );

		wp_mail( $to, '[' . get_bloginfo( 'name' ) . '] Weekly SEO report', implode( "\n", $lines ) );
	}
}
