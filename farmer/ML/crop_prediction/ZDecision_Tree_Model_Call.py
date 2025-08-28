import sys
import joblib
from collections import Counter

# Define header for the dataset
header = ['State_Name', 'District_Name', 'Season', 'Crop']

# Define the Question class for decision tree
class Question:
    def __init__(self, column, value):
        self.column = column
        self.value = value

    def match(self, example):
        val = example[self.column]
        return val == self.value

    def __repr__(self):
        return "Is %s %s %s?" % (header[self.column], "==", str(self.value))

# Define the Leaf class for decision tree
class Leaf:
    def __init__(self, Data):
        self.predictions = class_counts(Data)

# Define the Decision_Node class for decision tree
class Decision_Node:
    def __init__(self, question, true_branch, false_branch):
        self.question = question
        self.true_branch = true_branch
        self.false_branch = false_branch

# Function to count class labels
def class_counts(Data):
    counts = {}
    for row in Data:
        label = row[-1]
        if label not in counts:
            counts[label] = 0
        counts[label] += 1
    return counts

# Function to classify a row using the decision tree
def classify(row, node):
    if isinstance(node, Leaf):
        return node.predictions
    if node.question.match(row):
        return classify(row, node.true_branch)
    else:
        return classify(row, node.false_branch)

# Function to print leaf predictions
def print_leaf(counts):
    total = sum(counts.values()) * 1.0
    probs = {}
    for lbl in counts.keys():
        probs[lbl] = str(int(counts[lbl] / total * 100)) + "%"
    return probs

# Main script execution
if __name__ == "__main__":
    # Extract and clean arguments
    state = sys.argv[1].strip('"')  # Remove JSON encoding quotes
    district = sys.argv[2].strip('"')
    season = sys.argv[3].strip('"')

    # Load the decision tree model
    try:
        dt_model_final = joblib.load('ML/crop_prediction/filetest2.pkl')
    except Exception as e:
        print(f"Error loading model: {e}", file=sys.stderr)
        sys.exit(1)

    # Prepare testing data
    testing_data = [[state, district, season]]

    # Perform prediction
    for row in testing_data:
        try:
            predictions = classify(row, dt_model_final)
            predicted_crops = print_leaf(predictions)
            for crop, probability in predicted_crops.items():
                print(crop)  # Print the predicted crop(s) to stdout
        except Exception as e:
            print(f"Error during prediction: {e}", file=sys.stderr)