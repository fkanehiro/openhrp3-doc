#!/usr/bin/ruby

# Encoding.default_external = 'UTF-8'

target_dir = ARGV[0]

template_file = "#{target_dir}/template.html"

template = open(template_file).read

Dir.glob("#{target_dir}/*.block").each {|block_path|


  dir_name = File.dirname(block_path)
  html_path = dir_name + "/" + File.basename(block_path, ".block") + ".html"

  puts block_path + " => " + html_path

  variables = Hash.new
  blocktext = ""

  open(block_path) { |block|
    while line = block.gets
      if line =~ /^(\S+)\s*=(.*)$/
        variables[$1] = $2.strip
      elsif line =~ /^s*$/
        break
      end
    end

    blocktext = block.read
  }

  inserted = template.gsub(/##(.+)##/) { |matched|
    id = $1
    if id == "bodytext"
      blocktext
    elsif variables[id]
      variables[id]
    end
  }

  open(html_path, "w") { |html|
    html.write inserted
  }
}

  
