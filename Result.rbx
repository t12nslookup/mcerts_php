class Result < ActiveRecord::Base
  set_primary_key "resultid"
  set_sequence_name "result_seq"

  def self.save_all(results)
    transaction do
      for r in results
        r.save
      end
    end
  end
end
