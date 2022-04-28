class AtomScannerParser 
  
  def initialize file
    samples = Array.new
    parsing_element_information=false
    parsing_sample_information=false
    field_seperator=","
    
    for line in file
      line_columns=line.split(field_seperator)
      
      if line_columns[0].strip=="#" #new batch of samples
        parsing_sample_information = !parsing_sample_information
        columns = Array.new
        line_columns.each {|column|	columns.push(column.strip)}
        next  #goto next iteration of file-line loop
      else
        next if line_columns[0].to_i==0 or line_columns.size==1 #next iteration in loop if not line with sample on
      end
      
      sample_line_number=line_columns[0].to_i
      samples[sample_line_number]=Hash.new if samples[sample_line_number]==nil
      
      if parsing_sample_information
        columns.each_with_index {|column,index|  samples[sample_line_number][column]=line_columns[index].strip}
      else
        samples[sample_line_number]["elements"] = Hash.new if samples[sample_line_number]["elements"]==nil
        columns.each_with_index {|column,index|  samples[sample_line_number]["elements"][column]=line_columns[index].strip if index>1}		
      end
    end
    samples.delete_at 0 #there is no sample number 0
    ActiveRecord::Base.establish_connection(:adapter=>"oci",
    :host=>"v20z1:1521/LIMS2",
    :username=>"saltest",:password=>"oraclev1")
    
    x=0
    for sample in samples
      results = []	
      for key,value in sample["elements"]
        compound,concentration=key,value
        if concentration!=""
          result=Result.new(:compound=>compound,
                            :concentration=>concentration,
                            :sample=>sample["Sample Name"],
          :deleted=>1)
          results<<result
  			Result.save_all results
  			
  			print "Concentration : "+result.concentration.to_s+"\n" #if result.concentration.to_s!="" 
  			print "Conc2 : "+value.to_s+"\n\n" #if value!=""
  			x+=1
  			break if x>100
  		end
    end
  end
end
  