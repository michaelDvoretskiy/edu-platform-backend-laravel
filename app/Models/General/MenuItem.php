<?php

namespace App\Models\General;

use App\Models\General\Link;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class MenuItem extends Model
{
    use HasFactory;

    public static function getMenuLinks($menuName)
    {
        $sql = "select l.*
            from menu_items m inner join links l on m.link_id = l.id
            where m.type='menu_item' and m.parent_id in(
            SELECT id from menu_items where name = :menu and type='root')
            order by m.ord";
        $linkList = DB::select($sql, ['menu' => $menuName]);
        return Link::hydrate($linkList);
    }
}
