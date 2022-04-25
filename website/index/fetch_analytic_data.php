<?php
	require_once('../autoload.php');
	// Load the Google API PHP Client Library.
	require_once('../vendor/autoload.php');

	$analytics = initializeAnalytics();
	$response_today = getReport($analytics,"today");
	$response_thismonth = getReport($analytics,"thismonth");
	$response_thisyear = getReport($analytics,"thisyear");
	$response_lifetime = getReport($analytics,"lifetime");
	//printResults($response);
	$arrayResult["ANALYTIC_TODAY"] = printResults($response_today);
	$arrayResult["ANALYTIC_THISMONTH"] = printResults($response_thismonth);
	$arrayResult["ANALYTIC_THISYEAR"] = printResults($response_thisyear);
	$arrayResult["ANALYTIC_LIFETIME"] = printResults($response_lifetime);
	$arrayResult["RESULT"] = true;
	echo json_encode($arrayResult);


	/**
	 * Initializes an Analytics Reporting API V4 service object.
	 *
	 * @return An authorized Analytics Reporting API V4 service object.
	 */
	function initializeAnalytics()
	{

	  // Use the developers console and download your service account
	  // credentials in JSON format. Place them in this directory or
	  // change the key file location if necessary.
	  $KEY_FILE_LOCATION = '../service-account-credentials.json';

	  // Create and configure a new client object.
	  $client = new Google_Client();
	  $client->setApplicationName("Hello Analytics Reporting");
	  $client->setAuthConfig($KEY_FILE_LOCATION);
	  $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
	  $analytics = new Google_Service_AnalyticsReporting($client);

	  return $analytics;
	}


	/**
	 * Queries the Analytics Reporting API V4.
	 *
	 * @param service An authorized Analytics Reporting API V4 service object.
	 * @return The Analytics Reporting API V4 response.
	 */
	function getReport($analytics,$data_range) {

	  // Replace with your view ID, for example XXXX.
	  $VIEW_ID = "229513778";

	  if($data_range == "today"){
		  // Create the DateRange object.
		  $dateRange = new Google_Service_AnalyticsReporting_DateRange();
		  $dateRange->setStartDate("today");
		  $dateRange->setEndDate("today");
	  }else if($data_range == "thismonth"){
		  // Create the DateRange object.
			$m = date('m');
			$y = date('Y');
		  $dateRange = new Google_Service_AnalyticsReporting_DateRange();
		  $dateRange->setStartDate($y."-".$m."-01");
		  $dateRange->setEndDate("today");
	  }else if($data_range == "thisyear"){
		  // Create the DateRange object.
			$y = date('Y');
		  $dateRange = new Google_Service_AnalyticsReporting_DateRange();
		  $dateRange->setStartDate($y."-01-01");
		  $dateRange->setEndDate("today");
	  }else {
		  $dateRange = new Google_Service_AnalyticsReporting_DateRange();
		  $dateRange->setStartDate("2020-01-01");
		  $dateRange->setEndDate("today");
	  }

	  // Create the Metrics object.
	  $sessions = new Google_Service_AnalyticsReporting_Metric();
	  $sessions->setExpression("ga:sessions");
	  $sessions->setAlias("sessions");
	  
	  // Create the Metrics object.
	  $pageviews = new Google_Service_AnalyticsReporting_Metric();
	  $pageviews->setExpression("ga:pageviews");
	  $pageviews->setAlias("pageviews");

	  // Create the Metrics object.
	  $users = new Google_Service_AnalyticsReporting_Metric();
	  $users->setExpression("ga:users");
	  $users->setAlias("users");

	  // Create the ReportRequest object.
	  $request = new Google_Service_AnalyticsReporting_ReportRequest();
	  $request->setViewId($VIEW_ID);
	  $request->setDateRanges($dateRange);
	  $request->setMetrics(array($sessions,$pageviews,$users));

	  $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
	  $body->setReportRequests( array( $request) );
	  return $analytics->reports->batchGet( $body );
	}


	/**
	 * Parses and prints the Analytics Reporting API V4 response.
	 *
	 * @param An Analytics Reporting API V4 response.
	 */
	function printResults($reports) {
		
	  $data_report = [];
	  for ( $reportIndex = 0; $reportIndex < count( $reports ); $reportIndex++ ) {
		$report = $reports[ $reportIndex ];
		$header = $report->getColumnHeader();
		$dimensionHeaders = $header->getDimensions();
		$metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
		$rows = $report->getData()->getRows();

		for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
		  $data_report_item = [];
		  $row = $rows[ $rowIndex ];
		  $dimensions = $row->getDimensions();
		  $metrics = $row->getMetrics();
		  for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
			$data_report_item["dimensions"][] = [
				"dimensionHeaders" => $dimensionHeaders[$i],
				"dimensions" => $dimensions[$i],
			];
		  }

		  for ($j = 0; $j < count($metrics); $j++) {
			$values = $metrics[$j]->getValues();
			for ($k = 0; $k < count($values); $k++) {
			  $entry = $metricHeaders[$k];
			  $data_report_item["metric"][] = [
				"name" => $entry->getName(),
				"values" => $values[$k],
			  ];
			}
		  }
		  $data_report = $data_report_item;
		}
	  }
	  return $data_report;
	}

?>