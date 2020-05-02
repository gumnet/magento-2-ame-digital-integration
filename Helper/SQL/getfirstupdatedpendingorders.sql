SELECT so.entity_i,ao.increment_id, ao.ame_id,
so.state, so.status

FROM ame_order ao,
sales_order so

WHERE so.state IN ('new','processing')

ORDER BY ao.updated_at
LIMIT [LIMIT]