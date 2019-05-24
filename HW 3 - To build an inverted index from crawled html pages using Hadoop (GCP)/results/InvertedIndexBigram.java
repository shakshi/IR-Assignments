import java.io.IOException;
import java.util.HashMap;
import java.util.Map;
import java.util.StringTokenizer;

import org.apache.hadoop.fs.Path;
import org.apache.hadoop.io.LongWritable;
import org.apache.hadoop.io.Text;

import org.apache.hadoop.mapreduce.Job;
import org.apache.hadoop.mapreduce.Mapper;
import org.apache.hadoop.mapreduce.Reducer;
import org.apache.hadoop.mapreduce.lib.input.FileInputFormat;
import org.apache.hadoop.mapreduce.lib.output.FileOutputFormat;

class BigramMapper extends Mapper<LongWritable, Text, Text, Text> {

	private Text word = new Text();
	private Text word2 = new Text();
	private Text bigram = new Text();
	private Text docId = new Text();

	public void map(LongWritable key, Text value, Context context) throws IOException, InterruptedException {

		String line = value.toString();
		String splits[] = line.split("\\t", 2);

		docId.set(splits[0]);
		String doc = splits[1];
		doc = doc.toLowerCase();
		doc = doc.replaceAll("[^a-zA-Z]", " ");

		StringTokenizer tokenizer = new StringTokenizer(doc);
		if (tokenizer.hasMoreTokens()) {
			word.set(tokenizer.nextToken());
		}

		while (tokenizer.hasMoreTokens()) {
			word2.set(tokenizer.nextToken());
			String b = word.toString() + " " + word2.toString();
			bigram.set(b);

			context.write(bigram, docId);
			word.set(word2);
		}
	}
}

class BigramReducer extends Reducer<Text, Text, Text, Text> {

	public void reduce(Text key, Iterable<Text> values, Context context) throws IOException, InterruptedException {
		// for each bigram : hashmap of doc id and its count
		HashMap<String, Integer> M = new HashMap<>();

		for (Text docid : values) {

			String id = docid.toString();
			if (M.containsKey(id)) {
				M.put(id, M.get(id) + 1);
			} else {
				M.put(id, 1);
			}
		}

		String ans = "";
		for (Map.Entry<String, Integer> entry : M.entrySet()) {
			ans = ans + entry.getKey() + ":" + entry.getValue() + " ";
		}

		context.write(key, new Text(ans));
	}
}

public class InvertedIndexBigram {

	public static void main(String[] args) throws Exception {

		if (args.length != 2) {
			System.err.println("Err: Inverted Index ");
			System.exit(-1);
		}

		Job job = new Job();
		job.setJarByClass(InvertedIndexBigram.class);
		job.setJobName("Inverted Index Bigram");

		FileInputFormat.addInputPath(job, new Path(args[0]));
		FileOutputFormat.setOutputPath(job, new Path(args[1]));

		job.setMapperClass(BigramMapper.class);
		job.setReducerClass(BigramReducer.class);

		job.setOutputKeyClass(Text.class);
		job.setOutputValueClass(Text.class);

		job.waitForCompletion(true);
	}
}
