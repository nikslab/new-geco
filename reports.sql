
# Scores by generation
SELECT generation,min(score),avg(score),max(score) FROM new_geco.bots where experiment_id=2  group by generation order by generation desc;

# 
SELECT generation,count(distinct gene) FROM pd_moves right join bots on bot_id=bots.id where experiment_id=2 group by generation;

# Distinct gene-allele combo used by generation
SELECT generation, count(distinct gene, allele) FROM new_geco.genes right join bots on bot_id=bots.id where experiment_id=2 group by generation order by generation desc;
