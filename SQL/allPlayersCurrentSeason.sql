WITH period_season AS (
    SELECT 
        DATE(CONCAT(
            YEAR(CURDATE()) - CASE WHEN MONTH(CURDATE()) < 9 THEN 1 ELSE 0 END, 
            '-09-01'
        )) AS start_periode,
        DATE(CONCAT(
            YEAR(CURDATE()) + CASE WHEN MONTH(CURDATE()) >= 9 THEN 1 ELSE 0 END, 
            '-08-31'
        )) AS end_periode
)
SELECT *
FROM (
    SELECT DISTINCT winner_id AS user_id  
    FROM `fixture` f 
    INNER JOIN interclub_fixture icf ON (f.interclub_fixture_id = icf.id AND f.event_type_id = 'I')
    CROSS JOIN period_season p
    WHERE icf.fixture_date BETWEEN p.start_periode AND p.end_periode
    
    UNION
    
    SELECT DISTINCT loser_id AS user_id    
    FROM `fixture` f 
    INNER JOIN interclub_fixture icf ON (f.interclub_fixture_id = icf.id AND f.event_type_id = 'I')
    CROSS JOIN period_season p
    WHERE icf.fixture_date BETWEEN p.start_periode AND p.end_periode
) AS participants_interclub;