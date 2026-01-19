-- ============================================================================
-- Fix Duplicate PlayerID Values
-- ============================================================================
-- This script fixes duplicate playerid values in farkle_players table.
-- These duplicates prevent adding a UNIQUE constraint on playerid, which is
-- required for Challenge Mode foreign key relationships.
--
-- Problem: The farkle_players table has a composite primary key (playerid, email)
-- instead of just playerid, allowing duplicate playerid values.
--
-- Solution: Reassign duplicate playerid entries to new sequential playerid values.
-- ============================================================================

DO $$
DECLARE
    duplicate_record RECORD;
    new_playerid INTEGER;
BEGIN
    -- Find all playerid values that have duplicates
    FOR duplicate_record IN
        SELECT playerid, email, username
        FROM farkle_players
        WHERE playerid IN (
            SELECT playerid
            FROM farkle_players
            GROUP BY playerid
            HAVING COUNT(*) > 1
        )
        AND (email = 'undefined' OR email IS NULL OR username LIKE '%bot%' OR email LIKE '%@bot.local' OR email LIKE '%@farkledice.local')
        ORDER BY playerid, lastplayed NULLS FIRST
    LOOP
        -- Get the next available playerid
        SELECT COALESCE(MAX(playerid), 0) + 1 INTO new_playerid FROM farkle_players;

        RAISE NOTICE 'Reassigning playerid % (%, %) to new playerid %',
            duplicate_record.playerid,
            duplicate_record.username,
            duplicate_record.email,
            new_playerid;

        -- Update the player record
        UPDATE farkle_players
        SET playerid = new_playerid
        WHERE playerid = duplicate_record.playerid
          AND email = duplicate_record.email;

        -- Update related records in other tables
        UPDATE farkle_games_players
        SET playerid = new_playerid
        WHERE playerid = duplicate_record.playerid;

        UPDATE farkle_players_devices
        SET playerid = new_playerid
        WHERE playerid = duplicate_record.playerid;

        UPDATE farkle_friends
        SET sourceid = new_playerid
        WHERE sourceid = duplicate_record.playerid;

        UPDATE farkle_friends
        SET friendid = new_playerid
        WHERE friendid = duplicate_record.playerid;

        UPDATE farkle_achievements_players
        SET playerid = new_playerid
        WHERE playerid = duplicate_record.playerid;

        UPDATE farkle_tournament_participants
        SET playerid = new_playerid
        WHERE playerid = duplicate_record.playerid;

    END LOOP;

    RAISE NOTICE 'Duplicate playerid cleanup complete';
END $$;

-- Verify no duplicates remain
DO $$
DECLARE
    duplicate_count INTEGER;
BEGIN
    SELECT COUNT(*) - COUNT(DISTINCT playerid) INTO duplicate_count
    FROM farkle_players;

    IF duplicate_count > 0 THEN
        RAISE EXCEPTION 'Still have % duplicate playerid values after cleanup!', duplicate_count;
    ELSE
        RAISE NOTICE 'Verification successful: All playerid values are now unique';
    END IF;
END $$;
