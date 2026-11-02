<?php

namespace Tests\Unit;

use App\Services\Wisdom\ReadQueryGuard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/** The guard behind Wisdom AI's run_read_query tool: read-only, one statement, no auth tables or credentials, one resort. */
class ReadQueryGuardTest extends TestCase
{
    public static function allowed(): array
    {
        return [
            'one table' => ['SELECT Emp_id, status FROM employees WHERE resort_id = :resort_id'],
            'backticks' => ['SELECT `Emp_id` FROM `employees` WHERE `resort_id` = :resort_id'],
            'join, both scoped, ORDER BY with commas' => ['SELECT e.Emp_id, d.name FROM employees e JOIN resort_departments d ON d.id = e.Dept_id AND d.resort_id = :resort_id
                WHERE e.resort_id = :resort_id ORDER BY e.Emp_id, d.name DESC'],
            'IN list and GROUP BY' => ["SELECT status, COUNT(*) AS n FROM employees WHERE resort_id = :resort_id AND status IN ('Active', 'Inactive') GROUP BY status"],
            'subquery scoped too' => ['SELECT e.Emp_id FROM employees e WHERE e.resort_id = :resort_id AND e.Dept_id IN (SELECT d.id FROM resort_departments d WHERE d.resort_id = :resort_id)'],
            'OR and keywords inside a string are data' => ["SELECT Emp_id FROM employees WHERE resort_id = :resort_id AND notes LIKE '%delete or update; union%'"],
            'trailing semicolon and comment' => ['SELECT Emp_id FROM employees WHERE resort_id = :resort_id; -- latest'],
        ];
    }

    #[DataProvider('allowed')]
    public function test_allowed_queries_pass_and_get_a_limit_and_timeout(string $sql)
    {
        $r = ReadQueryGuard::validate($sql);
        $this->assertTrue($r['ok'], $r['error'] ?? '');
        $this->assertStringContainsString('LIMIT ' . ReadQueryGuard::MAX_ROWS, $r['sql']);
        $this->assertStringStartsWith('SELECT /*+ MAX_EXECUTION_TIME(' . ReadQueryGuard::TIMEOUT_MS . ') */', $r['sql']);
    }

    public function test_a_given_limit_is_kept()
    {
        $this->assertStringEndsWith('LIMIT 5', ReadQueryGuard::validate('SELECT Emp_id FROM employees WHERE resort_id = :resort_id LIMIT 5')['sql']);
    }

    public static function blocked(): array
    {
        return [
            'empty' => [''],
            'write' => ['DELETE FROM employees WHERE resort_id = :resort_id'],
            'write hidden behind a comment' => ['/* report */ UPDATE employees SET status = 1 WHERE resort_id = :resort_id'],
            'stacked statement' => ['SELECT Emp_id FROM employees WHERE resort_id = :resort_id; DROP TABLE employees'],
            'into outfile' => ["SELECT Emp_id FROM employees WHERE resort_id = :resort_id INTO OUTFILE '/tmp/x'"],
            'sleep' => ['SELECT SLEEP(10) FROM employees WHERE resort_id = :resort_id'],
            'union' => ['SELECT Emp_id FROM employees WHERE resort_id = :resort_id UNION SELECT Emp_id FROM employees WHERE resort_id <> :resort_id'],
            'information_schema' => ['SELECT table_name FROM information_schema.tables WHERE resort_id = :resort_id'],
            'no resort scope' => ['SELECT Emp_id FROM employees'],
            'password column' => ['SELECT password FROM employees WHERE resort_id = :resort_id'],
            'remember_token column' => ['SELECT remember_token FROM employees WHERE resort_id = :resort_id'],
            'auth table' => ['SELECT email FROM resort_admins WHERE resort_id = :resort_id'],
            'oauth table' => ['SELECT id FROM oauth_access_tokens WHERE resort_id = :resort_id'],
        ];
    }

    #[DataProvider('blocked')]
    public function test_unsafe_queries_are_refused(string $sql)
    {
        $this->assertFalse(ReadQueryGuard::validate($sql)['ok']);
    }

    /** Bypasses that passed the guard before 2026-11-02 (each read other resorts' rows or a blocked table). */
    public static function bypasses(): array
    {
        return [
            'OR widens the resort filter' => ['SELECT Emp_id FROM employees WHERE resort_id = :resort_id OR 1 = 1'],
            '|| is OR in MySQL' => ['SELECT Emp_id FROM employees WHERE resort_id = :resort_id || 1 = 1'],
            'comma join, second table unscoped' => ['SELECT x.Emp_id FROM employees e, employees x WHERE e.resort_id = :resort_id'],
            'comma join to an auth table' => ['SELECT r.email FROM employees e, resort_admins r WHERE e.resort_id = :resort_id'],
            'comma join after JOIN ... ON' => ['SELECT x.Emp_id FROM employees e JOIN resort_departments d ON d.id = e.Dept_id AND d.resort_id = :resort_id, employees x WHERE e.resort_id = :resort_id'],
            'comma join inside a subquery' => ['SELECT e.Emp_id FROM employees e WHERE e.resort_id = :resort_id AND e.id IN (SELECT x.id FROM employees x, employees y WHERE x.resort_id = :resort_id)'],
            'database-qualified table' => ['SELECT email FROM wisdom.resort_admins WHERE resort_id = :resort_id'],
            'STRAIGHT_JOIN' => ['SELECT x.Emp_id FROM employees e STRAIGHT_JOIN employees x WHERE e.resort_id = :resort_id'],
            'joined table unscoped' => ['SELECT x.Emp_id FROM employees e JOIN employees x ON x.id > 0 WHERE e.resort_id = :resort_id'],
            'one table scoped twice, the other not' => ['SELECT x.Emp_id FROM employees e JOIN employees x ON x.id > 0 WHERE e.resort_id = :resort_id AND e.resort_id = :resort_id'],
            'scope padded with a string literal' => ["SELECT x.Emp_id FROM employees e JOIN employees x ON x.id > 0 WHERE e.resort_id = :resort_id AND x.notes <> 'x.resort_id'"],
            ':resort_id inside an IN list' => ['SELECT Emp_id FROM employees WHERE resort_id IN (:resort_id, 27)'],
            ':resort_id with another operator' => ['SELECT Emp_id FROM employees WHERE resort_id <> :resort_id'],
        ];
    }

    #[DataProvider('bypasses')]
    public function test_known_bypasses_are_refused(string $sql)
    {
        $this->assertFalse(ReadQueryGuard::validate($sql)['ok']);
    }

    public function test_credential_columns_and_auth_tables_are_recognised()
    {
        foreach (['password', 'user_password', 'remember_token', 'two_factor_secret', 'api_token', 'otp'] as $c) {
            $this->assertTrue(ReadQueryGuard::isSensitiveColumn($c), $c);
        }
        foreach (['first_name', 'basic_salary', 'Emp_id'] as $c) {
            $this->assertFalse(ReadQueryGuard::isSensitiveColumn($c), $c);
        }
        foreach (['resort_admins', '`admins`', 'oauth_clients', 'migrations', 'sessions'] as $t) {
            $this->assertTrue(ReadQueryGuard::isDeniedTable($t), $t);
        }
        $this->assertFalse(ReadQueryGuard::isDeniedTable('employees'));
    }
}
