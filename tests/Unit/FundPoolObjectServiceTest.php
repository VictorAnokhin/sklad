<?php

namespace Tests\Unit;

use App\Services\FundPoolObjectService;
use Tests\TestCase;

class FundPoolObjectServiceTest extends TestCase
{
    public function test_it_extracts_package_id_from_struct_type(): void
    {
        $service = new FundPoolObjectService();
        $packageId = '0x799f69b5be95ddc5d1107912a74f6835bef6a80a6ede155cb843753940a92934';
        $type = $packageId.'::pool_manager::Pool<0xa1ec7fc00a6f40db9693ad1415d0c193ad3906494428cf252621037bd7117e29::usdc::USDC>';

        $this->assertSame($packageId, $service->extractPackageIdFromStructType($type));
    }

    public function test_it_normalizes_object_ids_to_66_characters(): void
    {
        $service = new FundPoolObjectService();

        $this->assertSame(
            '0x0000000000000000000000000000000000000000000000000000000000000001',
            $service->normalizeObjectId('0x1')
        );
    }

    public function test_it_shortens_object_ids_for_cli_output(): void
    {
        $service = new FundPoolObjectService();

        $this->assertSame(
            '0x799f69…a92934',
            $service->shortObjectId('0x799f69b5be95ddc5d1107912a74f6835bef6a80a6ede155cb843753940a92934')
        );
    }
}
